<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Console\ConfirmableTrait;

class SetupTest extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the test environment with fake data for testing purposes.';

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
        if (! $this->confirmToProceed()) {
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

        $this->info('Filling the database with fake data');
        $this->call('db:seed', ['--class' => 'FakeContentTableSeeder']);
        $this->info('Filled database with fake data');

        $this->info('You can now sign in with username: admin@admin.com and password: admin');
    }
}
