<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddActiveColumnOnServiceFrecuencyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cfg.ServiceFrecuency', function (Blueprint $table) {
            $table->boolean('Active')->default(true);
        });

        \App\ServiceFrecuency::where('ServiceFrecuencyId', 7)
            ->update(['Active' => false]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cfg.ServiceFrecuency', function (Blueprint $table) {
            $table->dropColumn('Active');
        });
    }
}
