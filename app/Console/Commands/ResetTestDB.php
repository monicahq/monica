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
    protected $signature = 'monica:resetdb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the testing database';

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
        if (env('APP_ENV') == 'local') {
            DB::table('tasks')->delete();
            DB::table('significant_others')->delete();
            DB::table('reminders')->delete();
            DB::table('peoples')->delete();
            DB::table('notes')->delete();
            DB::table('kids')->delete();
            DB::table('important_dates')->delete();
            DB::table('gifts')->delete();
            DB::table('entities')->delete();
            DB::table('contacts')->delete();
            DB::table('accounts')->delete();

            $this->info('Test database has been reset');
        } else {
            $this->info('Can\'t execute this command in this environment');
        }
    }
}
