<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Round;
use App\Models\RoundAdvancement;

class ExamEligibilityService
{
    public function canEnter(Candidate $candidate, Round $round): bool
    {
        if ($candidate->status !== 'active' || $round->status !== 'open') {
            return false;
        }

        if ($candidate->exam_event_id !== $round->exam_event_id) {
            return false;
        }

        return true;
    }
}
