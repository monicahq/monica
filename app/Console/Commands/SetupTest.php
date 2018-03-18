<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! $this->confirm('Are you sure you want to proceed? This will delete ALL data in your environment.')) {
            return;
        }

        $this->callSilent('migrate:fresh');
        $this->info('✓ Performed migrations');

        $this->call('db:seed', ['--class' => 'ActivityTypesTableSeeder']);
        $this->info('✓ Filled the Activity Types table');

        $this->call('db:seed', ['--class' => 'CountriesSeederTable']);
        $this->info('✓ Filled the Countries table');

        $this->callSilent('storage:link');
        $this->info('✓ Symlinked the storage folder for the avatars');

        if (! $this->option('skipSeed')) {
            $this->call('db:seed', ['--class' => 'FakeContentTableSeeder']);
            $this->info('');
            $this->info('✓ Filled database with fake data');
        }

        $this->line('');
        $this->line('-----------------------------');
        $this->line('|');
        $this->line('| Welcome to Monica v'.config('monica.app_version'));
        $this->line('|');
        $this->line('-----------------------------');
        $this->info('| You can now sign in to your account:');
        $this->line('| username: admin@admin.com');
        $this->line('| password: admin');
        $this->line('| URL:      '.config('app.url'));
        $this->line('-----------------------------');

        $this->info('Setup is done. Have fun.');
    }
}
