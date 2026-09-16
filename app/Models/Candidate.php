<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = [
        'exam_event_id','level_id','candidate_code','full_name','class_name',
        'computer_no','pin_hash','checkin_at','last_seen_at','last_ip','status'
    ];

    protected $hidden = ['pin_hash'];
    protected $casts = [
        'checkin_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function examEvent(): BelongsTo { return $this->belongsTo(ExamEvent::class); }
    public function level(): BelongsTo { return $this->belongsTo(Level::class); }
    public function attempts(): HasMany { return $this->hasMany(ExamAttempt::class); }
}
