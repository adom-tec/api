<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddContractTypeIdColumnOnProfessionalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cfg.professionals', function (Blueprint $table) {
            $table->integer('ContractTypeId')->unsigned()->nullable();

            $table->foreign('ContractTypeId')->references('Id')->on('cfg.ContractTypes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cfg.professionals', function (Blueprint $table) {
            $table->dropForeign(['ContractTypeId']);
            $table->dropColumn('ContractTypeId');
        });
    }
}
