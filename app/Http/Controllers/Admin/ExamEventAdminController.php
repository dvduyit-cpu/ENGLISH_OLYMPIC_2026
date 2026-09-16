<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamEventAdminController extends Controller
{
    public function index()
    {
        $events = ExamEvent::withCount('candidates')->with('rounds')->latest('id')->get();
        return view('admin.events', compact('events'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data, $request) {
            $event = ExamEvent::create($this->eventPayload($data, $request));
            $this->syncRounds($event, $data);
        });
        return back()->with('message', 'Đã tạo kỳ thi mới.');
    }

    public function update(Request $request, ExamEvent $event)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($event, $data, $request) {
            $event->update($this->eventPayload($data, $request));
            $this->syncRounds($event, $data);
        });
        return back()->with('message', 'Đã cập nhật kỳ thi '.$event->name.'.');
    }

    public function destroy(ExamEvent $event)
    {
        if ($event->candidates()->exists()) return back()->withErrors(['event' => 'Không thể xóa kỳ thi đã có thí sinh. Hãy chuyển trạng thái sang Kết thúc.']);
        $event->delete();
        return back()->with('message', 'Đã xóa kỳ thi.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'=>['required','string','max:255'],'starts_at'=>['required','date'],'ends_at'=>['required','date','after:starts_at'],
            'status'=>['required','in:draft,active,finished'],'exam_level'=>['required','in:KET,PET'],
            'round_1_minutes'=>['required','integer','min:1','max:300'],'round_2_minutes'=>['required','integer','min:1','max:300'],'round_3_minutes'=>['required','integer','min:1','max:300'],
        ]);
    }

    private function eventPayload(array $data, Request $request): array
    {
        return ['name'=>$data['name'],'exam_date'=>date('Y-m-d',strtotime($data['starts_at'])),'starts_at'=>$data['starts_at'],'ends_at'=>$data['ends_at'],'allow_ket'=>$data['exam_level'] === 'KET','allow_pet'=>$data['exam_level'] === 'PET','status'=>$data['status']];
    }

    private function syncRounds(ExamEvent $event, array $data): void
    {
        $definitions = [1=>['ROUND 1 – GENERAL KNOWLEDGE',20],2=>['ROUND 2 – LANGUAGE KNOWLEDGE',30],3=>['ROUND 3 – SKILLS CHALLENGE',30]];
        foreach ($definitions as $order => [$name,$questions]) { $round = $event->rounds()->firstOrNew(['round_order'=>$order]); $round->fill(['name'=>$name,'number_questions'=>$questions,'time_limit_seconds'=>$data['round_'.$order.'_minutes']*60]); if (!$round->exists) $round->status = 'waiting'; $round->save(); }
    }
}