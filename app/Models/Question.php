<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'level_id','category_id','question_type','question_text',
        'passage_text','audio_path','image_path','points','is_active'
    ];
    protected $casts = ['is_active' => 'boolean', 'points' => 'decimal:2'];

    public function level(): BelongsTo { return $this->belongsTo(Level::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function options(): HasMany { return $this->hasMany(QuestionOption::class); }
    public function roundAssignments(): HasMany { return $this->hasMany(RoundQuestion::class); }
}
