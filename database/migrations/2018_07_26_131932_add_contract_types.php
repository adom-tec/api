<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\ContractType;

class AddContractTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cfg.ContractTypes', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('Name');
        });

        ContractType::insert([
            [
                'Name' => 'PRESTACIÓN DE SERVICIOS'
            ],
            [
                'Name' => 'CONTRATO LABORAL'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cfg.ContractTypes');
    }
}
