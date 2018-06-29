<?php

use Illuminate\Database\Seeder;
use App\CancelReason;

class CancelReasonTableSeeder extends Seeder
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
                'name' => 'Fallecimiento'
            ],
            [
                'name' => 'Hospitalización'
            ],
            [
                'name' => 'Mejoría'
            ],
            [
                'name' => 'Horario'
            ],
            [
                'name' => 'Solicitud Entidad',
            ],
            [
                'name' => 'Factor Económico'
            ],
            [
                'name' => 'Motivo Viaje'
            ],
            [
                'name' => 'Solicitud del Paciente'
            ],
            [
                'name' => 'Otro'
            ]
        ];

        CancelReason::insert($reasons);
    }
}
