<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SegmentCategory extends Model
{
    //
    protected $table = "event_segment_category";

    protected $fillable = [
        'percent',
        'event_id',
        'segment_id',
        'category_id'
    ];
}
