<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsValidDateAuthorizationNumberOnAssignServiceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sas.AssignService', function (Blueprint $table) {
            $table->date('InitDateAuthorizationNumber')->nullable();
            $table->date('FinalDateAuthorizationNumber')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sas.AssignService', function (Blueprint $table) {
            $table->dropColumn(['InitDateAuthorizationNumber', 'FinalDateAuthorizationNumber']);
        });
    }
}
