<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\ExamEvent;
use App\Models\SpeakingCriterion;
use App\Models\SpeakingScore;
use App\Models\SpeakingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpeakingAdminController extends Controller
{
    public function index()
    {
        $event = ExamEvent::query()->where('status','active')->latest('id')->firstOrFail();
        $sessions = SpeakingSession::query()->where('exam_event_id',$event->id)
            ->with(['candidate.level','scores'])->get();
        $candidates = Candidate::query()->where('exam_event_id',$event->id)
            ->with('level')->orderBy('candidate_code')->get();
        $criteria = SpeakingCriterion::orderBy('id')->get();

        return view('admin.speaking', compact('event','sessions','candidates','criteria'));
    }

    public function addCandidate(Request $request)
    {
        $data = $request->validate([
            'exam_event_id' => ['required','exists:exam_events,id'],
            'candidate_id' => ['required','exists:candidates,id'],
            'room' => ['nullable','string','max:100'],
        ]);

        $candidate = Candidate::findOrFail($data['candidate_id']);
        abort_unless($candidate->exam_event_id == $data['exam_event_id'], 422);

        SpeakingSession::updateOrCreate(
            ['exam_event_id'=>$data['exam_event_id'],'candidate_id'=>$data['candidate_id']],
            ['room'=>$data['room'] ?? null,'status'=>'waiting']
        );

        return back()->with('message','Đã thêm thí sinh vào Speaking Challenge.');
    }

    public function score(Request $request, SpeakingSession $session)
    {
        $criteria = SpeakingCriterion::all();
        $rules = ['comment'=>['nullable','string','max:2000']];
        foreach ($criteria as $criterion) {
            $rules['criterion_'.$criterion->id] = ['required','numeric','min:0','max:'.$criterion->max_score];
        }
        $data = $request->validate($rules);

        DB::transaction(function () use ($request,$session,$criteria,$data) {
            foreach ($criteria as $criterion) {
                SpeakingScore::updateOrCreate(
                    [
                        'speaking_session_id'=>$session->id,
                        'judge_id'=>$request->user()->id,
                        'criterion_id'=>$criterion->id,
                    ],
                    [
                        'score'=>$data['criterion_'.$criterion->id],
                        'comment'=>$data['comment'] ?? null,
                    ]
                );
            }
            $session->update([
                'status'=>'finished',
                'started_at'=>$session->started_at ?: now(),
                'finished_at'=>now(),
            ]);
        });

        return back()->with('message','Đã lưu điểm Speaking.');
    }
}
