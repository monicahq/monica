<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class SetupProduction extends Command
{

    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:production {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform setup of Monica.';

    /**
     * Create a new command instance.
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
        if (! $this->confirmToProceed()) {
            return;
        }

        /*
         * If the .env file does not exist, then key generation
         * will fail. So we create one if it does not already exist.
         */
        if( ! file_exists('.env') )
        {
            touch('.env');
        }

        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--class' => 'ActivityTypesTableSeeder', '--force' => true]);
        $this->call('db:seed', ['--class' => 'CountriesSeederTable', '--force' => true]);
        $this->call('storage:link');
    }
}
