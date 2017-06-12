<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupHeroku extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:heroku {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform setup of Monica when deployed to Heroku.';

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
        /**
         * Check if the .env file exists, if not create one.
         * If a .env file is absent, key generation will fail.
         */
        if( !file_exists('.env') )
        {
            touch('.env');
            file_put_contents('.env', "HEROKU=true\r\nDB_CONNECTION=heroku");
        }

        $this->call('key:generate', ['--force' => true]);
        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--class' => 'ActivityTypesTableSeeder', '--force' => true]);
        $this->call('db:seed', ['--class' => 'CountriesSeederTable', '--force' => true]);
        $this->call('storage:link');
    }
}
