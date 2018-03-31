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
            } else {
                //$this->line(DB::connection('pgsql')->table($table_name)->from);
            }
        }
    }

    private function checkTableColumns()
    {
        $mysql = DB::connection('mysql');
        $mtables = $mysql->select('show tables');

        foreach($mtables as $mtable) {
            $thisTable = $mysql->select('show columns from '. $mtable->Tables_in_monica );
            $dataTable = $mysql->select('select * from '. $mtable->Tables_in_monica );

            
            foreach($thisTable as $row){
                $this->line($row->Field);
            }

            break;
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
        $this->checkTableColumns();
        $this->line("Inconsistent tables: ");
        $this->line($this->inconsistence_table_count);


    }
}
