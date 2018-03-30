<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Forklift extends Command
{
    /**
     * Return the names of the MySQL tables.
     * 
     * @return array
     */
    private function mysqlTables()
    {
        return array_map(function($table) { return $table->Tables_in_monica; },
                         DB::connection('mysql')->select('show tables'));
    }

    /**
     * Read and return all rows from the given MySQL table.
     * 
     * @param string $tableName
     * @return array
     */
    private function readMysqlTable($tableName)
    {
        return DB::connection('mysql')->table($tableName)->get();
    }

    /**
     * Fills the specified PostgreSQL table with the given rows.
     * 
     * @param string $tableName
     * @param array $rows
     */
    private function fillPgsqlTable($tableName, $rows)
    {
        foreach ($rows as $row)
            DB::connection('pgsql')->table($tableName)->insert(get_object_vars($row));
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
        $this->call('migrate:fresh', ['--database' => 'pgsql']);
        $tables = $this->mysqlTables();
        foreach ($tables as $table)
        {
            $rows = $this->readMysqlTable($table);
            $this->fillPgsqlTable($table, $rows);
        }
    }
}
