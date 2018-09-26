<?php

use Illuminate\Database\Seeder;

class WorkScheduleRangeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $workScheduleRanges = [
            [
                'WorkScheduleId' => '1',
                'Start' => '07:00:00',
                'End' => '17:00:00'
            ],
            [
                'WorkScheduleId' => '1',
                'Start' => '19:00:00',
                'End' => '21:00:00'
            ],
            [
                'WorkScheduleId' => '2',
                'Start' => '17:00:00',
                'End' => '19:00:00'
            ],
            [
                'WorkScheduleId' => '2',
                'Start' => '06:00:00',
                'End' => '07:00:00'
            ],
            [
                'WorkScheduleId' => '3',
                'Start' => '21:00:00',
                'End' => '05:00:00'
            ],
            [
                'WorkScheduleId' => '4',
                'Start' => '05:00:00',
                'End' => '06:00:00'
            ]
        ];

        \App\WorkScheduleRange::insert($workScheduleRanges);
    }
}
