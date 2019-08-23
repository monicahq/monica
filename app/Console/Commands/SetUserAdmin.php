<?php

namespace App\Console\Commands;

use App\Models\User\User;
use Illuminate\Console\Command;

class SetUserAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:admin
                            {--force : Force the operation to run when in production.}
                            {--email= : The email of the user whose admin status you want to change}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Toggle administrator privileges for a user';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // retrieve the email from the option
        $email = $this->option('email');

        // if no email was passed to the option, prompt the user to enter the email
        if (! $email) {
            $email = $this->ask('What is the user’s email?');
        }

        // retrieve the user with the specified email
        $user = User::where('email', $email)->first();

        if (! $user) {
            // show an error and exist if the user does not exist
            $this->error('No user with that email.');

            return;
        }

        // Print a warning
        if ($user->admin) {
            $this->warn($user->email.' will be removed from the administrators of this instance');
        } else {
            $this->warn($user->email.' will be added to the administrators of this instance');
        }

        // ask for confirmation if not forced
        if (! $this->option('force') && ! $this->confirm('Do you wish to continue?')) {
            return;
        }

        // toglle admin status
        $user->admin = ! $user->admin;
        $user->save();

        // Show new status
        if ($user->admin) {
            $this->info($user->email.' has been added to the administrators of this instance');
        } else {
            $this->info($user->email.' has been removed from the administrators of this instance');
        }
    }
}
