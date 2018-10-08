<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkScheduleRangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cfg.WorkScheduleRanges', function (Blueprint $table) {
            $table->increments('Id');
            $table->integer('WorkScheduleId')->unsigned();
            $table->time('Start');
            $table->time('End');
            $table->timestamps();

            $table->foreign('WorkScheduleId')->references('id')->on('cfg.WorkSchedules');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cfg.WorkScheduleRanges');
    }
}
