<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVisitsCollectionAccount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sas.VisitCollectionAcount', function (Blueprint $table) {
            $table->increments('Id');
            $table->integer('CollectionAccountId')->unsigned();
            $table->integer('AssignServiceDetailId')->unsigned();

            $table->foreign('CollectionAccountId')->references('id')->on('sas.CollectionAccounts');
            $table->foreign('AssignServiceDetailId')->references('AssignServiceDetailId')->on('sas.AssignServiceDetails');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sas.VisitCollectionAcount');
    }
}
