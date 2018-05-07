<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventCategoryCriteriaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_category_criteria', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('event_id')->unsigned();
            $table->integer('category_id')->unsigned();
            $table->integer('criteria_id')->unsigned();
            $table->foreign('category_id')->references('id')->on('categories');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');;
            $table->foreign('criteria_id')->references('id')->on('criterias');
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
        Schema::dropIfExists('program');
    }
}
