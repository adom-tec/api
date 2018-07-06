<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnRateIdToAssignServiceDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sas.AssignServiceDetails', function (Blueprint $table) {
            $table->integer('professional_rate_id')->unsigned()->default(1);
            $table->foreign('professional_rate_id')->references('id')->on('cfg.ProfesionalRates');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sas.AssignServiceDetails', function (Blueprint $table) {
            //
        });
    }
}
