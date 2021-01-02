<?php

namespace App\Console\Commands;

use App\Models\Account\Account;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class CreateAccount extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'account:create
                            {--email= : Login email for the account.}
                            {--password= : Password to set for the account.}
                            {--firstname= : First name for the account.}
                            {--lastname= : Last name for the account.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new account';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $email = $this->option('email');
        if (empty($email)) {
            $this->error('! You must specify an email');
        }

        $password = $this->option('password');
        if (empty($password)) {
            $this->error('! You must specify a password');
        }

        $firstName = $this->option('firstname') ?? 'John';

        $lastName = $this->option('lastname') ?? 'Doe';

        if (empty($email) || empty($password)) {
            return;
        }

        if ($this->confirmToProceed('This will create a new user for '.$firstName.' '.$lastName.' with email '.$email)) {
            Account::createDefault($firstName, $lastName, $email, $password);

            $this->info('| You can now sign in to your account:');
            $this->line('| username: '.$email);
            $this->line('| password: <hidden>');
        }
    }
}
