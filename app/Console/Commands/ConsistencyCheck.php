<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;


class ConsistencyCheck extends Command
{

    private $count;

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

    private function checkTables()
    {

        $mysql = DB::connection('mysql');
        $mtables = $mysql->select('show tables');
        var_dump($mtables[0]);

        foreach($mtables as $mtable) {
            if (DB::connection('pgsql')->table($mtable) != null) {
                $this->count++;
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
        //$mysql = DB::connection('mysql');
        //$pgsql = DB::connection('pgsql');

        //$mtables = $mysql->select('show tables');
        //$ptables = $pgsql->select('select * from');

        $this->checkTables();
        $this->line($this->count);

    }
}
