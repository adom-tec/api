<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeAuthorizationNumberColumnToNullableOnAssignServiceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sas.AssignService', function (Blueprint $table) {
            $table->string('AuthorizationNumber', 300)->nullable()->change();
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
            $table->string('AuthorizationNumber', 300)->change();
        });
    }
}
