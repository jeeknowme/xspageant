<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CandidateEvent extends Model
{
    //
    protected $table = 'candidate_event';

    protected $fillable = [
        'user_id',
        'event_id',
        'number'
    ];  
}
