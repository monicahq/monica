<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;


class ConsistencyCheck extends Command
{

    private $inconsistence_table_count = 0;
    private $inconsistence_columns_count = 0;
    private $inconsistence_data_count = 0;



    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:consistencycheck';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * Check if tables in mysql exist in pgsql
     * Increment $inconsistence_table_count if it doesn't
     *
     * @return void
     */
    private function checkTables()
    {
        $mysql = DB::connection('mysql');
        $mtables = $mysql->select('show tables');

        foreach($mtables as $mtable) {
            $table_name = $mtable->Tables_in_monica;
            if (DB::connection('pgsql')->table($table_name)->from != $table_name) {
                $this->inconsistence_table_count++;
            }
        }
    }

    /**
     * Check if tables have the same column names
     * Increment $inconsistence_columns_count if it doesn't
     *
     * @return void
     */
    private function checkTableColumnsNamesMatch()
    {
        $mysql = DB::connection('mysql');
        $pgsql = DB::connection('pgsql');
        $mtables = $mysql->select('show tables');

        foreach($mtables as $mtable) {
            $thisTable = $mysql->select('show columns from '. $mtable->Tables_in_monica );
            $pgTable = $pgsql->getSchemaBuilder()->getColumnListing($mtable->Tables_in_monica);

            sort($pgTable);
            sort($thisTable);


            for($x = 0; $x < count($thisTable); $x++){
                if($thisTable[$x]->Field != $pgTable[$x]) {
                    $this->inconsistence_columns_count++;
                }
            }
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->inconsistence_table_count = 0;
        //$mysql = DB::connection('mysql');
        //$pgsql = DB::connection('pgsql');

        //$mtables = $mysql->select('show tables');
        //$ptables = $pgsql->select('select * from');

        $this->checkTables();
        $this->checkTableColumnsNamesMatch();
        $this->line("Inconsistent tables: ");
        $this->line($this->inconsistence_table_count);
        $this->line("Inconsistent table columns: ");
        $this->line($this->inconsistence_columns_count);

    }
}
