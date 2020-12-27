<?php

namespace App\Console\Commands;

use function Safe\touch;
use App\Helpers\InstanceHelper;
use App\Models\Account\Account;
use Illuminate\Console\Command;

class CreateAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'account:create
                            {--email= : Login email for the account.}
                            {--password= : Password to set for the account.}';

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
            $this.error('! You must specify an email');
            return;
        }
        
        $password = $this->option('password');
        if (empty($password)) {
            $this.error('! You must specify a password');
            return;
        }

        Account::createDefault('John', 'Doe', $email, $password);

        $this->info('| You can now sign in to your account:');
        $this->line('| username: '.$email);
        $this->line('| password: <hidden>');
    }
}
