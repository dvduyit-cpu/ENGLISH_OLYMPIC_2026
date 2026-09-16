<?php

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\Round;
use App\Models\RoundAdvancement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdvancementService
{
    public function selectForNextRound(Round $fromRound, Round $toRound): int
    {
        if ($toRound->round_order !== $fromRound->round_order + 1) {
            throw new RuntimeException('Vòng tiếp theo không hợp lệ.');
        }

        $fromRound->load('quotas');
        if ($fromRound->quotas->isEmpty()) {
            throw new RuntimeException('Chưa cấu hình quota KET/PET cho vòng này.');
        }

        return DB::transaction(function () use ($fromRound, $toRound) {
            RoundAdvancement::query()->where('to_round_id', $toRound->id)->delete();
            $selected = 0;

            foreach ($fromRound->quotas as $quota) {
                if ($quota->advance_count < 1) {
                    continue;
                }

                $attempts = ExamAttempt::query()
                    ->join('candidates', 'candidates.id', '=', 'exam_attempts.candidate_id')
                    ->where('exam_attempts.round_id', $fromRound->id)
                    ->whereIn('exam_attempts.status', ['submitted', 'auto_submitted'])
                    ->where('candidates.level_id', $quota->level_id)
                    ->orderByDesc('exam_attempts.score')
                    ->orderBy('exam_attempts.elapsed_ms')
                    ->orderBy('exam_attempts.submitted_at')
                    ->limit($quota->advance_count)
                    ->get(['exam_attempts.candidate_id']);

                foreach ($attempts as $attempt) {
                    RoundAdvancement::create([
                        'from_round_id' => $fromRound->id,
                        'to_round_id' => $toRound->id,
                        'candidate_id' => $attempt->candidate_id,
                    ]);
                    $selected++;
                }
            }

            return $selected;
        });
    }
}
