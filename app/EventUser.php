<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventUser extends Model
{
    //
    protected $table = "event_user";
    
    protected $fillable = [
        'user_id',
        'event_id'
    ];
}
