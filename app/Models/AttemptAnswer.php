<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptAnswer extends Model
{
    protected $fillable = [
        'attempt_id','question_id','option_id','text_answer','is_correct','points_awarded','answered_at'
    ];
    protected $casts = ['is_correct' => 'boolean', 'answered_at' => 'datetime'];

    public function attempt(): BelongsTo { return $this->belongsTo(ExamAttempt::class, 'attempt_id'); }
    public function question(): BelongsTo { return $this->belongsTo(Question::class); }
    public function option(): BelongsTo { return $this->belongsTo(QuestionOption::class, 'option_id'); }
}
