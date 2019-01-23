<?php

use Illuminate\Database\Seeder;

class ReasonSuspensionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $reasons = [
            [
                'name' => 'Viaje'
            ],
            [
                'name' => 'Hospitalización'
            ],
            [
                'name' => 'Solicitud Médica'
            ],
            [
                'name' => 'Tratamiento Paralelo'
            ],
            [
                'name' => 'Factor Económico'
            ],
            [
                'name' => 'Decisión Familiar'
            ],
            [
                'name' => 'No orden Médica'
            ]
        ];

        for ($i = 0; $i < count($reasons); $i++) {
            $reasons[$i]['name'] = mb_strtoupper($reasons[$i]['name']);
        }

        \App\ReasonSuspensionService::insert($reasons);
    }
}
