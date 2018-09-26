<?php

use Illuminate\Database\Seeder;

class WorkSchedulesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $worksSchedules = [
            [
                'name' => 'HORA BÁSICA'
            ],
            [
                'name' => 'HORA EXTRA DIURNA'
            ],
            [
                'name' => 'RECARGO NOCTURNO'
            ],
            [
                'name' => 'HORA EXTRA NOCTURNA'
            ]
        ];
        \App\WorkSchedule::insert($worksSchedules);
    }
}
