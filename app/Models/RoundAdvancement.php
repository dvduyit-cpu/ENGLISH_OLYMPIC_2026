<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoundAdvancement extends Model
{
    protected $fillable = ['from_round_id','to_round_id','candidate_id'];
}
