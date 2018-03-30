<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;


class ConsistencyCheck extends Command
{



    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'datamigration:check';

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
        Event::listen(['eloquent.saved: *', 'eloquent.created: *'], function() {
            //
        });
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //$mysql = DB::connection('mysql');
        $pgsql = DB::connection('pgsql');

        //$mtables = $mysql->select('show tables');
        $ptables = $pgsql->select('show * from test');


        if($ptables == null) {
            foreach ($ptables as $table) {

                $this->line($table);
            }
        } else {
            $this->line($ptables);
        }

    }
}
