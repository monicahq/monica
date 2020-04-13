<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:test_email
                            {--email= : The email address to send to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to check that delivery is working';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // retrieve the email from the option
        $email = $this->option('email');

        // if no email was passed to the option, prompt the user to enter the email
        if (! $email) {
            $email = $this->ask('What email address should I send the test email to?');
        }

        $this->info("Preparing and sending email to '".$email."'");

        // immediately deliver the test email (bypassing the queue)
        Mail::raw(
            'Hi '.$email.', you requested a test email from Monica.',
            function ($message) use ($email) {
                $message->to($email)
                    ->subject('Monica email delivery test');
            }
        );

        $this->info('Email sent!');
    }
}
