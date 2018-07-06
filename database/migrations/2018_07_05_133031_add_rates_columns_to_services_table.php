<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRatesColumnsToServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cfg.Services', function (Blueprint $table) {
            $table->float('special_value')->default(0);
            $table->float('particular_value')->default(0);
            $table->float('holiday_value')->default(0);
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
            $table->dropColumn(['special_value', 'particular_value', 'holiday_value']);
        });
    }
}
