<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\ExamEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CandidateAuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->routeIs('candidate.login')) return redirect()->route('login');
        $events = ExamEvent::query()->where('status','active')->where(fn($q)=>$q->whereNull('ends_at')->orWhere('ends_at','>=',now()))
            ->orderBy('starts_at')->orderBy('name')->get();
        return view('auth.login', ['portal'=>$request->query('portal')==='admin'?'admin':'candidate','events'=>$events]);
    }

    public function login(Request $request)
    {
        $data=$request->validate(['exam_selection'=>['required','regex:/^\\d+:(KET|PET)$/'],'candidate_code'=>['required','string','max:50'],'pin'=>['required','string','max:50']]);
        [$eventId, $selectedLevel] = explode(':', $data['exam_selection'], 2);
        $event=ExamEvent::query()->whereKey($eventId)->where('status','active')
            ->where(fn($q)=>$q->whereNull('starts_at')->orWhere('starts_at','<=',now()))
            ->where(fn($q)=>$q->whereNull('ends_at')->orWhere('ends_at','>=',now()))->first();
        if(!$event)return back()->withErrors(['exam_selection'=>'Kỳ thi chưa mở hoặc đã kết thúc.'])->withInput($request->except('pin'));
        $candidate=Candidate::query()->where('exam_event_id',$event->id)->where('candidate_code',trim($data['candidate_code']))->where('status','active')->whereHas('level', fn($q)=>$q->where('code',$selectedLevel))->with('level')->first();
        $levelAllowed=$candidate && (($selectedLevel==='KET'&&$event->allow_ket)||($selectedLevel==='PET'&&$event->allow_pet));
        if(!$candidate||!$levelAllowed||!Hash::check($data['pin'],$candidate->pin_hash))return back()->withErrors(['candidate_code'=>'Kỳ thi, SBD hoặc PIN không đúng.'])->withInput($request->except('pin'));
        $candidate->update(['checkin_at'=>$candidate->checkin_at?:now(),'last_seen_at'=>now(),'last_ip'=>$request->ip()]);
        $request->session()->regenerate();$request->session()->put('candidate_id',$candidate->id);
        return redirect()->route('candidate.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('candidate_id');$request->session()->regenerateToken();
        return redirect()->route('login');
    }
}