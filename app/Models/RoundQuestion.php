<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoundQuestion extends Model
{
    protected $fillable = ['round_id','level_id','question_id','sort_order'];

    public function round(): BelongsTo { return $this->belongsTo(Round::class); }
}
