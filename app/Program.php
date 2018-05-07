<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $table = 'event_category_criteria';

    protected $fillable = [
        'event_id',
        'category_id',
        'criteria_id'
    ];
}
