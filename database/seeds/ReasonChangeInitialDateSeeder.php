<?php

use Illuminate\Database\Seeder;

class ReasonChangeInitialDateSeeder extends Seeder
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
                'name' => 'Solicitud del usuario'
            ],
            [
                'name' => 'Terminación de orden anterior'
            ],
            [
                'name' => 'Agenda de terapeuta'
            ],
            [
                'name' => 'Comienza tratamiento antes de la fecha creado el servicio'
            ],
            [
                'name' => 'solicitud del paciente'
            ]
        ];

        for ($i = 0; $i < count($reasons); $i++) {
            $reasons[$i]['name'] = mb_strtoupper($reasons[$i]['name']);
        }

        \App\ReasonChangeInitDate::insert($reasons);
    }
}
