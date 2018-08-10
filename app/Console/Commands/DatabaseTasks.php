<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class DatabaseTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'database-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta tareas relacionadas con la base de datos';

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
        $date = Carbon::now()->format('Y-m-d');
        \DB::statement("EXEC AdomServices.sas.UpdateServiceStatus '$date'");
        exec("sqlcmd -S localhost -U sa -P " . config('database.connections.sqlsrv.password') . " -Q \"BACKUP DATABASE [AdomServices] TO DISK = N'/var/opt/mssql/data/AdomServices(${date}).bak' WITH NOFORMAT, NOINIT, NAME = 'AdomServices', SKIP, NOREWIND, NOUNLOAD, STATS = 10\"");
    }
}
