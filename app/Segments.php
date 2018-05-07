<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Segments extends Model
{
    //

     protected $fillable = [
        'number'
    ];

    public function events(){
       return $this->belongsToMany('App\Event');
    }

}
