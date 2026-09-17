<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\ExamAttempt;
use App\Models\ExamEvent;
use App\Models\Round;
use Illuminate\Http\Request;
class AdminDashboardController extends Controller
{
 public function __invoke(Request $request)
 {
  $events=ExamEvent::latest('id')->get();
  $event=$request->integer('event_id')?$events->firstWhere('id',$request->integer('event_id')):($events->firstWhere('status','active')??$events->first());
  $rounds=collect();$leaderboard=collect();$candidateCount=$onlineCount=$workingCount=$submittedCount=0;
  if($event){
   $rounds=Round::where('exam_event_id',$event->id)->orderBy('round_order')->get()->map(function($round){$round->working_count=ExamAttempt::where('round_id',$round->id)->where('status','in_progress')->count();$round->submitted_count=ExamAttempt::where('round_id',$round->id)->whereIn('status',['submitted','auto_submitted'])->count();return $round;});
   $candidateCount=$event->candidates()->count();$onlineCount=$event->candidates()->where('last_seen_at','>=',now()->subMinutes(2))->count();
   $attemptQuery=ExamAttempt::whereHas('candidate',fn($q)=>$q->where('exam_event_id',$event->id));$workingCount=(clone $attemptQuery)->where('status','in_progress')->count();$submittedCount=(clone $attemptQuery)->whereIn('status',['submitted','auto_submitted'])->count();
   $leaderboard=Candidate::where('exam_event_id',$event->id)->with(['level','attempts'=>fn($q)=>$q->whereIn('status',['submitted','auto_submitted'])])->get()->map(function($candidate){$candidate->total_score=(float)$candidate->attempts->sum('score');$candidate->total_correct=$candidate->attempts->sum('correct_answers');$candidate->total_elapsed=$candidate->attempts->sum('elapsed_ms');$candidate->rounds_completed=$candidate->attempts->count();return $candidate;})->filter(fn($c)=>$c->rounds_completed>0)->sort(function($a,$b){return $b->total_score<=>$a->total_score ?: $a->total_elapsed<=>$b->total_elapsed;})->values()->take(10);
  }
  return view('admin.dashboard',compact('events','event','rounds','leaderboard','candidateCount','onlineCount','workingCount','submittedCount'));
 }
}