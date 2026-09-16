<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoundQuota extends Model
{
    protected $fillable = ['round_id','level_id','advance_count'];
    public function round(): BelongsTo { return $this->belongsTo(Round::class); }
    public function level(): BelongsTo { return $this->belongsTo(Level::class); }
}
