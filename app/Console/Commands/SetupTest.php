<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SetupTest extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:test
                            {--skipSeed : Whether we should populate the database with fake data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the test environment with optional fake data for testing purposes.';

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
        if (! $this->confirm('Are you sure you want to proceed? This will delete ALL data in your environment.')) {
            return;
        }

        $this->line('Performing all migrations');
        $this->callSilent('migrate:fresh');
        $this->info('Performed migrations');

        $this->line('Filling the Activity Types table');
        $this->call('db:seed', ['--class' => 'ActivityTypesTableSeeder']);
        $this->info('Filled the Activity Types table');

        $this->line('Filling the Countries table');
        $this->call('db:seed', ['--class' => 'CountriesSeederTable']);
        $this->info('Filled the Countries table');

        if (! $this->option('skipSeed')) {
            $this->info('Filling the database with fake data');
            $this->call('db:seed', ['--class' => 'FakeContentTableSeeder']);
            $this->info('Filled database with fake data');
            $this->info('You can now sign in with username: admin@admin.com and password: admin');
        }

        $this->info('Setup is done. Have fun.');
    }
}
