<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\AttemptAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Round;
use App\Models\RoundQuestion;
use App\Services\ExamEligibilityService;
use App\Services\RankingService;
use App\Services\ScoringService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    public function start(Request $request, Round $round, ExamEligibilityService $eligibility)
    {
        $candidate = $request->attributes->get('candidate');

        abort_unless($eligibility->canEnter($candidate, $round), 403, 'Bạn chưa được phép vào vòng thi này.');

        $questionCount = RoundQuestion::query()
            ->join('questions', 'questions.id', '=', 'round_questions.question_id')
            ->where('questions.is_active', true)
            ->where('round_questions.round_id', $round->id)
            ->where('round_questions.level_id', $candidate->level_id)
            ->count();

        if ($questionCount < $round->number_questions) {
            return back()->withErrors(['exam' => 'Bộ đề chưa đủ số câu theo cấu hình vòng thi.']);
        }

        $attempt = ExamAttempt::firstOrCreate(
            ['candidate_id' => $candidate->id, 'round_id' => $round->id],
            ['started_at' => now(), 'status' => 'in_progress']
        );

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('candidate.exam.completed', [$round, $attempt]);
        }

        return redirect()->route('candidate.exam.show', [$round, $attempt]);
    }

    public function show(Request $request, Round $round, ExamAttempt $attempt)
    {
        $candidate = $request->attributes->get('candidate');
        abort_unless($attempt->candidate_id === $candidate->id && $attempt->round_id === $round->id, 403);
        abort_unless($attempt->status === 'in_progress', 403, 'Bài thi đã kết thúc.');

        if ($this->remainingSeconds($attempt, $round) <= 0) {
            $this->finalize($attempt, $round, true);
            return redirect()->route('candidate.exam.completed', [$round, $attempt])->with('message', 'Hết giờ. Hệ thống đã tự động nộp bài.');
        }

        $questions = Question::query()
            ->select('questions.*')
            ->join('round_questions', 'round_questions.question_id', '=', 'questions.id')
            ->where('round_questions.round_id', $round->id)
            ->where('round_questions.level_id', $candidate->level_id)
            ->where('questions.is_active', true)
            ->orderBy('round_questions.sort_order')
            ->limit($round->number_questions)
            ->with(['options' => fn ($q) => $q->select('id','question_id','option_code','option_text')->orderBy('option_code')])
            ->get();

        $answers = AttemptAnswer::query()
            ->where('attempt_id', $attempt->id)
            ->get()->keyBy('question_id');

        $remainingSeconds = $this->remainingSeconds($attempt, $round);

        return view('candidate.exam', compact('candidate','round','attempt','questions','answers','remainingSeconds'));
    }

    public function saveAnswer(Request $request, Round $round, ExamAttempt $attempt)
    {
        $candidate = $request->attributes->get('candidate');
        abort_unless($attempt->candidate_id === $candidate->id && $attempt->round_id === $round->id, 403);

        if ($attempt->status !== 'in_progress' || $this->remainingSeconds($attempt, $round) <= 0) {
            return response()->json(['ok' => false, 'message' => 'Bài thi đã hết giờ.'], 409);
        }

        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'option_id' => ['nullable', 'integer'],
            'text_answer' => ['nullable', 'string', 'max:500'],
        ]);

        $question = Question::query()
            ->whereKey($data['question_id'])
            ->whereHas('roundAssignments', fn ($query) => $query
                ->where('round_id', $round->id)
                ->where('level_id', $candidate->level_id))
            ->firstOrFail();

        if ($question->answer_mode === 'text') {
            $textAnswer = trim((string) ($data['text_answer'] ?? ''));
            if ($textAnswer === '') {
                AttemptAnswer::where('attempt_id', $attempt->id)->where('question_id', $question->id)->delete();
                return response()->json(['ok' => true, 'saved_at' => now()->toIso8601String()]);
            }
            AttemptAnswer::updateOrCreate(
                ['attempt_id' => $attempt->id, 'question_id' => $question->id],
                ['option_id' => null, 'text_answer' => $textAnswer, 'answered_at' => now()]
            );
        } else {
            abort_unless(!empty($data['option_id']), 422, 'Vui lòng chọn một đáp án.');
            $option = QuestionOption::query()
                ->whereKey($data['option_id'])
                ->where('question_id', $question->id)
                ->firstOrFail();
            AttemptAnswer::updateOrCreate(
                ['attempt_id' => $attempt->id, 'question_id' => $question->id],
                ['option_id' => $option->id, 'text_answer' => null, 'answered_at' => now()]
            );
        }

        return response()->json(['ok' => true, 'saved_at' => now()->toIso8601String()]);
    }

    public function submit(Request $request, Round $round, ExamAttempt $attempt)
    {
        $candidate = $request->attributes->get('candidate');
        abort_unless($attempt->candidate_id === $candidate->id && $attempt->round_id === $round->id, 403);

        if ($attempt->status === 'in_progress') {
            $this->finalize($attempt, $round, $this->remainingSeconds($attempt, $round) <= 0);
        }

        return redirect()->route('candidate.exam.completed', [$round, $attempt])->with('message', 'Đã nộp bài thành công.');
    }

    public function completed(Request $request, Round $round, ExamAttempt $attempt)
    {
        $candidate = $request->attributes->get('candidate');
        abort_unless($attempt->candidate_id === $candidate->id && $attempt->round_id === $round->id, 403);
        abort_if($attempt->status === 'in_progress', 403, 'Bạn chưa hoàn thành Round này.');

        $nextRound = Round::query()
            ->where('exam_event_id', $round->exam_event_id)
            ->where('round_order', $round->round_order + 1)
            ->first();

        $nextQuestionCount = 0;
        $canStartNext = false;
        $nextAttempt = null;
        if ($nextRound) {
            $nextQuestionCount = RoundQuestion::query()
                ->join('questions', 'questions.id', '=', 'round_questions.question_id')
                ->where('round_questions.round_id', $nextRound->id)
                ->where('round_questions.level_id', $candidate->level_id)
                ->where('questions.is_active', true)
                ->count();
            $canStartNext = $nextRound->status === 'open' && $nextQuestionCount >= $nextRound->number_questions;
            $nextAttempt = ExamAttempt::where('candidate_id', $candidate->id)->where('round_id', $nextRound->id)->first();
        }

        $allRounds = Round::where('exam_event_id', $round->exam_event_id)->orderBy('round_order')->get();
        return view('candidate.completed', compact('candidate','round','attempt','nextRound','nextAttempt','nextQuestionCount','canStartNext','allRounds'));
    }
    private function remainingSeconds(ExamAttempt $attempt, Round $round): int
    {
        $started = CarbonImmutable::parse($attempt->started_at);
        $endsAt = $started->addSeconds($round->time_limit_seconds);
        return max(0, now()->diffInSeconds($endsAt, false));
    }

    private function finalize(ExamAttempt $attempt, Round $round, bool $auto): void
    {
        DB::transaction(function () use ($attempt, $round, $auto) {
            $locked = ExamAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            if ($locked->status !== 'in_progress') {
                return;
            }

            $now = now();
            $elapsed = min(
                $round->time_limit_seconds * 1000,
                max(0, $locked->started_at->diffInMilliseconds($now))
            );

            $locked->update([
                'submitted_at' => $now,
                'elapsed_ms' => $elapsed,
                'status' => $auto ? 'auto_submitted' : 'submitted',
            ]);
        });

        app(ScoringService::class)->score($attempt->fresh());
        app(RankingService::class)->rebuild($round);
    }
}
