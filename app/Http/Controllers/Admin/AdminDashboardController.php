<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\ExamEvent;
use App\Models\Round;

class AdminDashboardController extends Controller
{
    public function __invoke()
    {
        $event = ExamEvent::where('status','active')->latest('id')->first() ?? ExamEvent::latest('id')->first();
        $rounds = collect(); $attempts = collect(); $candidateCount = 0; $onlineCount = 0;
        if ($event) {
            $rounds = Round::where('exam_event_id',$event->id)->with('quotas.level')->orderBy('round_order')->get()->map(function (Round $round) {
                $round->submitted_count = ExamAttempt::where('round_id',$round->id)->whereIn('status',['submitted','auto_submitted'])->count();
                $round->working_count = ExamAttempt::where('round_id',$round->id)->where('status','in_progress')->count();
                return $round;
            });
            $candidateCount = $event->candidates()->count();
            $onlineCount = $event->candidates()->where('last_seen_at','>=',now()->subMinutes(2))->count();
            $attempts = ExamAttempt::query()->whereHas('candidate',fn($q)=>$q->where('exam_event_id',$event->id))
                ->with(['candidate.level','round'])->withCount('answers')->latest('updated_at')->limit(100)->get();
        }
        return view('admin.dashboard',compact('event','rounds','attempts','candidateCount','onlineCount'));
    }
}