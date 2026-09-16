<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpeakingScore extends Model
{
    protected $fillable = ['speaking_session_id','judge_id','criterion_id','score','comment'];
}
