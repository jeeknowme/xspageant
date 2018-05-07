<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('score')->unsigned();
            $table->integer('event_id')->unsigned();
            $table->integer('category_id')->unsigned();
            $table->integer('criteria_id')->unsigned();
            $table->integer('judge_id')->unsigned();
            $table->integer('candidate_id')->unsigned();
            $table->foreign('event_id')->references('id')->on('events');
            $table->foreign('category_id')->references('id')->on('categories');
            $table->foreign('criteria_id')->references('id')->on('criterias');
            $table->foreign('judge_id')->references('id')->on('users');
            $table->foreign('candidate_id')->references('id')->on('candidates');
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
        Schema::dropIfExists('scores');
    }
}
