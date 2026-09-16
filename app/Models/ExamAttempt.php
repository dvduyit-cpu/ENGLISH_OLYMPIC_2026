<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    protected $fillable = [
        'candidate_id','round_id','started_at','submitted_at','elapsed_ms',
        'score','correct_answers','wrong_answers','status','rank'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'score' => 'decimal:2',
    ];

    public function candidate(): BelongsTo { return $this->belongsTo(Candidate::class); }
    public function round(): BelongsTo { return $this->belongsTo(Round::class); }
    public function answers(): HasMany { return $this->hasMany(AttemptAnswer::class, 'attempt_id'); }
}
