<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeBreakTimeColumnToIntOnServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cfg.Services', function (Blueprint $table) {
            $table->dropColumn('BreakTime');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cfg.Services', function (Blueprint $table) {
            $table->time('BreakTime')->nullable();
        });
    }
}
