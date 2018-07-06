<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(CancelReasonTableSeeder::class);
        $this->call(ProfesionalRatesTableSeeder::class);
    }
}
