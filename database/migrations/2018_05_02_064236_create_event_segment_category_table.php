<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventSegmentCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_segment_category', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('percent')->unsigned()->nullable();
            $table->integer('event_id')->unsigned();
            $table->integer('segment_id')->unsigned();
            $table->integer('category_id')->unsigned();
            $table->foreign('event_id')->references('id')->on('events');
            $table->foreign('segment_id')->references('id')->on('segments');
            $table->foreign('category_id')->references('id')->on('categories');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_segment_category');
    }
}
