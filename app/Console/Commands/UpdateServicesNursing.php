<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateServicesNursing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-services-nursing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza servicios de enfermería';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \DB::statement("EXEC AdomServices.sas.UpdateServiceStatus '$date'");
    }
}
