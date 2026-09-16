<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Round extends Model
{
    protected $fillable = [
        'exam_event_id','name','round_order','number_questions',
        'time_limit_seconds','status','opened_at','closed_at'
    ];

    protected $casts = ['opened_at' => 'datetime', 'closed_at' => 'datetime'];

    public function examEvent(): BelongsTo { return $this->belongsTo(ExamEvent::class); }
    public function quotas(): HasMany { return $this->hasMany(RoundQuota::class); }
    public function attempts(): HasMany { return $this->hasMany(ExamAttempt::class); }
}
