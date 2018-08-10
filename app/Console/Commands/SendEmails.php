<?php

namespace App\Console\Commands;

use App\Http\Controllers\PatientServiceController;
use App\Mail\IrregularServices;
use App\Mail\ServicesWithoutProfessional;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía correos automáticos del sistema';

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
        $date = Carbon::now()->format('d/m/Y');
        $serviceController = new PatientServiceController();
        //$countIrregularServices = count($serviceController->getIrregularServices());
        //$countServicesWithoutProfessional = count($serviceController->getServicesWithoutProfessional());
        $countServicesWithoutProfessional = 55;
        $countIrregularServices = 31;
        \Mail::to(['jefeterapias@adom.com.co','operaciones4@adom.com.co'])->send(new IrregularServices($date, $countIrregularServices));
        \Mail::to('operaciones4@adom.com.co')->send(new ServicesWithoutProfessional($date, $countServicesWithoutProfessional));
    }
}
