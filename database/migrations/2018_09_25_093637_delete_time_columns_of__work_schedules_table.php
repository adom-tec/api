<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteTimeColumnsOfWorkSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cfg.WorkSchedules', function (Blueprint $table) {
            $table->dropColumn(['End', 'Start']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cfg.WorkSchedules', function (Blueprint $table) {
            $table->time('Start');
            $table->time('End');
        });
    }
}
