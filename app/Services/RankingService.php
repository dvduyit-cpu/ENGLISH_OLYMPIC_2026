<?php

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\Round;
use Illuminate\Support\Facades\DB;

class RankingService
{
    public function rebuild(Round $round): void
    {
        DB::transaction(function () use ($round) {
            $attempts = ExamAttempt::query()
                ->where('round_id', $round->id)
                ->whereIn('status', ['submitted', 'auto_submitted'])
                ->orderByDesc('score')
                ->orderBy('elapsed_ms')
                ->orderBy('submitted_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $rank = 1;
            foreach ($attempts as $attempt) {
                $attempt->update(['rank' => $rank++]);
            }
        });
    }
}
