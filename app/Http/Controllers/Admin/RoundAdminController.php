<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\Level;
use App\Models\Round;
use App\Models\RoundQuota;
use App\Services\AdvancementService;
use App\Services\RankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoundAdminController extends Controller
{
    public function open(Round $round)
    {
        $round->update(['status' => 'open', 'opened_at' => now(), 'closed_at' => null]);
        return back()->with('message', 'Đã mở '.$round->name.'.');
    }

    public function openAll()
    {
        Round::query()->update(['status' => 'open', 'opened_at' => now(), 'closed_at' => null]);
        return back()->with('message', 'Đã mở tất cả các Round.');
    }

    public function closeAll()
    {
        Round::query()->where('status', 'open')->update(['status' => 'closed', 'closed_at' => now()]);
        return back()->with('message', 'Đã đóng tất cả các Round.');
    }

    public function close(Round $round)
    {
        $round->update(['status' => 'closed', 'closed_at' => now()]);
        return back()->with('message', 'Đã đóng '.$round->name.'.');
    }

    public function updateQuota(Request $request, Round $round)
    {
        $levels = Level::all();
        $rules = [];
        foreach ($levels as $level) {
            $rules['quota_'.$level->id] = ['required','integer','min:0','max:10000'];
        }
        $data = $request->validate($rules);

        foreach ($levels as $level) {
            RoundQuota::updateOrCreate(
                ['round_id' => $round->id, 'level_id' => $level->id],
                ['advance_count' => $data['quota_'.$level->id]]
            );
        }

        return back()->with('message', 'Đã lưu quota vòng thi.');
    }

    public function results(Round $round, RankingService $ranking)
    {
        $ranking->rebuild($round);

        $attempts = ExamAttempt::query()
            ->where('round_id', $round->id)
            ->whereIn('status', ['submitted','auto_submitted'])
            ->with('candidate.level')
            ->orderBy('rank')
            ->paginate(100);

        return view('admin.results', compact('round','attempts'));
    }

    public function advance(Round $round, AdvancementService $service)
    {
        $nextRound = Round::query()
            ->where('exam_event_id', $round->exam_event_id)
            ->where('round_order', $round->round_order + 1)
            ->first();

        if (!$nextRound) {
            return back()->withErrors(['advance' => 'Đây là vòng cuối, không có vòng tiếp theo.']);
        }

        try {
            $count = $service->selectForNextRound($round, $nextRound);
        } catch (\Throwable $e) {
            return back()->withErrors(['advance' => $e->getMessage()]);
        }

        return back()->with('message', "Đã chọn {$count} thí sinh vào {$nextRound->name}.");
    }
}
