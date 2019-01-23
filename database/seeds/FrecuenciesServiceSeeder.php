<?php

use Illuminate\Database\Seeder;

class FrecuenciesServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $frecuencies = [
            [
                'Name' => '4 Veces por Semana'
            ],
            [
                'Name' => '1 semanal'
            ],
            [
                'Name' => '5 Veces por Semana'
            ]
        ];

        for ($i = 0; $i < count($frecuencies); $i++) {
            $frecuencies[$i]['Name'] = mb_strtoupper($frecuencies[$i]['Name']);
        }

        \App\ServiceFrecuency::insert($frecuencies);
    }
}
