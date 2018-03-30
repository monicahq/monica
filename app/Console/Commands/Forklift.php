<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Forklift extends Command
{
    /**
     * Return the names of the MySQL tables.
     * 
     * @return array
     */
    private function mysqlTables()
    {
        return DB::select('show tables');
    }

    /**
     * Read and return all rows from the given MySQL table.
     * 
     * @param string $tableName
     * @return array
     */
    private function readMysqlTable($tableName)
    {
        return DB::select('select * from ' . $tableName);
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:forklift';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate data from MySQL to PostgreSQL';

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
        $this->line('test');
    }
}
