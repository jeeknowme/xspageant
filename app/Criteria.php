<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Criteria extends Model
{
    //
    protected $fillable = [
        'name',
        'percent'
    ];

    public function categories(){
        return $this->belongsToMany('App\Category');
    }
    
    public function events(){
        return $this->belongsToMany('App\Events');
    }

    public function programs(){
        return $this->hasMany('App\Programs');
    }
}
