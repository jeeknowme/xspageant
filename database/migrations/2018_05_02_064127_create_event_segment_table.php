<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventSegmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_segment', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('lemet')->unsigned()->nullable();
            $table->integer('reset')->unsigned()->default(0);
            $table->integer('percent')->unsigned()->nullable();
            $table->integer('event_id')->unsigned();
            $table->integer('segment_id')->unsigned();
            $table->foreign('event_id')->references('id')->on('events');
            $table->foreign('segment_id')->references('id')->on('segments');
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
        Schema::dropIfExists('event_segment');
    }
}
