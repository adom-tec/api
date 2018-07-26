<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\ProfesionalRate;

class CreateProfessionalRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cfg.ProfesionalRates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('Name');
        });

        $rates = [
            [
                'name' => 'ESTÁNDAR'
            ],
            [
                'name' => 'TARIFA ESPECIAL'
            ],
            [
                'name' => 'PARTICULAR'
            ],
            [
                'name' => 'DOMINICAL , FESTIVO'
            ]
        ];

        ProfesionalRate::insert($rates);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cfg.ProfesionalRates');
    }
}
