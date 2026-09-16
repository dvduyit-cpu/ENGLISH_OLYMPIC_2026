<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamEvent extends Model
{
    protected $fillable = ['name', 'exam_date', 'starts_at', 'ends_at', 'allow_ket', 'allow_pet', 'status'];
    protected $casts = ['exam_date' => 'date', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'allow_ket' => 'boolean', 'allow_pet' => 'boolean'];

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class)->orderBy('round_order');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }
}
