<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\Round;
use App\Models\RoundQuestion;
use App\Services\ExamEligibilityService;
use Illuminate\Http\Request;

class CandidateDashboardController extends Controller
{
    public function __invoke(Request $request, ExamEligibilityService $eligibility)
    {
        $candidate = $request->attributes->get('candidate');

        $rounds = Round::query()
            ->where('exam_event_id', $candidate->exam_event_id)
            ->orderBy('round_order')
            ->get()
            ->map(function (Round $round) use ($candidate, $eligibility) {
                $attempt = ExamAttempt::query()
                    ->where('candidate_id', $candidate->id)
                    ->where('round_id', $round->id)
                    ->first();

                $questionCount = RoundQuestion::query()->join('questions', 'questions.id', '=', 'round_questions.question_id')->where('round_questions.round_id', $round->id)->where('round_questions.level_id', $candidate->level_id)->where('questions.is_active', true)->count();
                $round->setAttribute('question_count', $questionCount);
                $round->setAttribute('can_enter', $eligibility->canEnter($candidate, $round) && $questionCount >= $round->number_questions);
                $round->setAttribute('attempt_record', $attempt);
                return $round;
            });

        return view('candidate.dashboard', compact('candidate', 'rounds'));
    }
}
