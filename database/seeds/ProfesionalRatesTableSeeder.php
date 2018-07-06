<?php

use Illuminate\Database\Seeder;
use App\ProfesionalRate;

class ProfesionalRatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
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
}
