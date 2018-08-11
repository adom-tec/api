<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeObservationToNullableOnCollectionAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    \DB::statement("ALTER TABLE sas.CollectionAccounts ALTER COLUMN observation text NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sas.CollectionAccounts', function (Blueprint $table) {
            $table->text('observation')->change();
        });
    }
}
