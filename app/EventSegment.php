<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventSegment extends Model
{
    //
    protected $table = "event_segment";
    

    protected $fillable = [
        'event_id',
        'segment_id',
        'limit',
        'reset',
        'percent'
    ];
}
