<?php

use Illuminate\Database\Seeder;
use App\State;

class InsertSUSPENDIDAState extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $state = new State();
	$state->Name = 'SUSPENDIDA';
	$state->save();
    }
}
