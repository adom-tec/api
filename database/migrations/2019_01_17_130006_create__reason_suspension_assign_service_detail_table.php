<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReasonSuspensionAssignServiceDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sas.ReasonSuspensionAssignServiceDetail', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('AssignServiceDetailId')->unsigned();
            $table->integer('ReasonSuspensionId')->unsigned();
            $table->timestamps();

            $table->foreign('AssignServiceDetailId')->references('AssignServiceDetailId')->on('sas.AssignServiceDetails');
            $table->foreign('ReasonSuspensionId')->references('id')->on('sas.ReasonsSuspensionService');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sas.ReasonSuspensionAssignServiceDetail');
    }
}
