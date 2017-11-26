<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class ResetTestDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the dev environment for testing purposes';

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
        if (config('app.env') == 'local') {
            foreach (\DB::select('SHOW TABLES') as $table) {
                $table_array = get_object_vars($table);
                \Schema::drop($table_array[key($table_array)]);
            }

            //$this->call('migrate');
            //$this->call('db:seed');
            $this->info('Local database has been reset');
        } else {
            $this->info('Can\'t execute this command in this environment');
        }
    }
}
