<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpeakingSession extends Model
{
    protected $fillable = ['exam_event_id','candidate_id','room','started_at','finished_at','status'];
    protected $casts = ['started_at'=>'datetime','finished_at'=>'datetime'];
    public function candidate(): BelongsTo { return $this->belongsTo(Candidate::class); }
    public function scores(): HasMany { return $this->hasMany(SpeakingScore::class); }
}
