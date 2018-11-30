<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAuthorizationNumberColumnOnAssignServiceDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sas.AssignServiceDetails', function (Blueprint $table) {
            $table->string('AuthorizationNumber', 15)->nullable();
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
            $table->dropColumn('AuthorizationNumber');
        });
    }
}
