<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    //
    protected $fillable = [
        'gender',
        'firstname',
        'lastname',
        'middlename'
        // 'event_id'
    ];

    public function events(){
        return $this->belongsToMany('App\Event')->withPivot('number');
    }
}
