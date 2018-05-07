<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    //

    protected $fillable = [
        'name',
        'place',
        'date',
        'user_id'
    ];

    public function users(){
        return $this->belongsToMany('App\User')->withPivot('judge_number');
    }

    public function candidates(){
        return $this->belongsToMany('App\Candidate')->withPivot('number');
    }

    public function categories(){
        return $this->belongsToMany('App\Category');
    }

    public function criterias(){
        return $this->belongsToMany('App\Criteria');
    }

    public function segments(){
        return $this->hasMany('App\Segments');
    }

    //pwede sya
    // public function products(){
    //     return $this->belongsToMany('App\Product','product_shops','shops_id','products_id') 
    //                                             //if plural and pivot table first currrent model then 
    //                                             // model being joined
    // }
}
