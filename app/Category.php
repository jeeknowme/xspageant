<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    //
    protected $fillable = [
        'name'
    ];

    public function criterias(){
        return $this->belongsToMany('App\Criteria');
    }

    public function events(){
        return $this->belongsToMany('App\Event');
    }

    public function programs(){
        return $this->hasMany('App\Programs');
    }
}

