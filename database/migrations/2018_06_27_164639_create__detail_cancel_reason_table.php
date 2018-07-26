<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDetailCancelReasonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sas.DetailCancelReason', function (Blueprint $table) {
            $table->increments('DetailCancelReasonId');
            $table->integer('AssignServiceDetailId')->unsigned();
            $table->integer('CancelReasonId')->unsigned();

            $table->foreign('AssignServiceDetailId')->references('AssignServiceDetailId')->on('sas.AssignServiceDetails');
            $table->foreign('CancelReasonId')->references('id')->on('sas.CancelReasons');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sas.DetailCancelReason');
    }
}
