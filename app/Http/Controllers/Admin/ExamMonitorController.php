<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\ExamEvent;
use App\Models\Level;
use App\Models\Round;
use Illuminate\Http\Request;
class ExamMonitorController extends Controller
{
 public function __invoke(Request $request)
 {
  $events=ExamEvent::latest('id')->get();
  $event=$request->integer('event_id')?$events->firstWhere('id',$request->integer('event_id')):($events->firstWhere('status','active')??$events->first());
  $rounds=$event?Round::where('exam_event_id',$event->id)->orderBy('round_order')->get():collect();
  $levels=Level::orderBy('id')->get();
  $baseQuery=ExamAttempt::query()
   ->when($event,fn($q)=>$q->whereHas('candidate',fn($c)=>$c->where('exam_event_id',$event->id)))
   ->when($request->integer('round_id'),fn($q,$id)=>$q->where('round_id',$id))
   ->when($request->integer('level_id'),fn($q,$id)=>$q->whereHas('candidate',fn($c)=>$c->where('level_id',$id)))
   ->when($request->filled('status'),fn($q)=>$q->where('status',$request->status));
  $working=(clone $baseQuery)->where('status','in_progress')->count();
  $submitted=(clone $baseQuery)->whereIn('status',['submitted','auto_submitted'])->count();
  $attempts=$baseQuery->with(['candidate.level','round'])->withCount('answers')->latest('updated_at')->paginate(100)->withQueryString();
  return view('admin.monitor',compact('events','event','rounds','levels','attempts','working','submitted'));
 }
}