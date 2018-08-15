<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un Backup de la base de datos';

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
        $date = Carbon::now()->format('Y_m_d__H_i');
        exec("sqlcmd -S localhost -U sa -P '" . config('database.connections.sqlsrv.password') . "' -Q \"BACKUP DATABASE [AdomServices] TO DISK = N'/var/opt/mssql/data/AdomServices(${date}).bak' WITH NOFORMAT, NOINIT, NAME = 'AdomServices', SKIP, NOREWIND, NOUNLOAD, STATS = 10\"");
    }
}
