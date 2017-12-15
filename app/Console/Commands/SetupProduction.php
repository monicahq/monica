<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class SetupProduction extends Command
{
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
        if (! $this->confirm('You are about to setup and configure Monica. Do you wish to continue?')) {
            return;
        }

        /*
         * If the .env file does not exist, then key generation
         * will fail. So we create one if it does not already exist.
         */
        if (! file_exists('.env')) {
            touch('.env');
        }

        $this->callSilent('migrate', ['--force' => true]);
        $this->info('✓ Performed migrations');

        $this->call('db:seed', ['--class' => 'ActivityTypesTableSeeder', '--force' => true]);
        $this->info('✓ Filled the Activity Types table');

        $this->call('db:seed', ['--class' => 'CountriesSeederTable', '--force' => true]);
        $this->info('✓ Filled the Countries table');

        $this->callSilent('storage:link');
        $this->info('✓ Symlinked the storage folder for the avatars');

        $email = $this->ask('Account creation: what should be your email address to login?');
        $password = $this->secret('Please choose a password:');

        // populate account table
        $accountID = DB::table('accounts')->insertGetId([
            'api_key' => str_random(30),
        ]);

        // populate user table
        $userId = DB::table('users')->insertGetId([
            'account_id' => $accountID,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => $email,
            'password' => bcrypt($password),
            'timezone' => config('app.timezone'),
            'remember_token' => str_random(10),
        ]);

        $this->line('');
        $this->line('-----------------------------');
        $this->line('|');
        $this->line('| Welcome to Monica v'.config('monica.app_version'));
        $this->line('|');
        $this->line('-----------------------------');
        $this->info('| You can now sign in to your account:');
        $this->line('| username: '.$email);
        $this->line('| password: <hidden>');
        $this->line('| URL:      '.config('app.url'));
        $this->line('-----------------------------');

        $this->info('Setup is done. Have fun.');
    }
}
