<?php

namespace App\Console\Commands;

use function Safe\touch;
use App\Models\Account\Account;
use Illuminate\Console\Command;

class SetupProduction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:production
                            {--force : Force the operation to run when in production.}
                            {--email= : Login email for the first account.}
                            {--password= : Password to set for the first account.}
                            {--skipSeed : Skip the populate database process.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform setup of Monica.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ((! $this->option('force')) && (! $this->confirm('You are about to setup and configure Monica. Do you wish to continue?'))) {
            return;
        }

        /*
         * If the .env file does not exist, then key generation
         * will fail. So we create one if it does not already exist.
         */
        if (! file_exists(__DIR__.'/../../../.env')) {
            touch(__DIR__.'/../../../.env');
        }

        $this->call('monica:update', ['--force' => true]);

        if (! $this->option('skipSeed')) {
            $this->line('✓ Filling database');
            $this->call('db:seed', ['--force' => true]);
        }

        $this->line('');
        $this->line('-----------------------------');
        $this->line('|');
        $this->line('| Welcome to Monica v'.config('monica.app_version'));
        $this->line('|');
        $this->line('-----------------------------');

        $email = $this->option('email');
        $password = $this->option('password');
        if (! empty($email) && ! empty($password)) {
            Account::createDefault('John', 'Doe', $email, $password);

            $this->info('| You can now sign in to your account:');
            $this->line('| username: '.$email);
            $this->line('| password: <hidden>');
        } elseif (Account::hasAny()) {
            $this->info('| You can now log in to your account');
        } else {
            $this->info('| You can now register to the first account by opening the application:');
        }

        $this->line('| URL:      '.config('app.url'));
        $this->line('-----------------------------');

        $this->info('Setup is done. Have fun.');
    }
}
