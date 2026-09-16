<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpeakingCriterion extends Model
{
    protected $fillable = ['name','max_score','weight'];
}
