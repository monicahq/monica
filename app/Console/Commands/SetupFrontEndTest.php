<?php

namespace App\Console\Commands;

use App\Account;
use Illuminate\Console\Command;

class SetupFrontEndTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:frontendtesting';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the test environment exclusively for front-end testing with Cypress.';

    private $account;

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
        $this->artisan('migrate:fresh');
        $this->artisan('db:seed', ['--class' => 'ActivityTypesTableSeeder']);
        $this->account = Account::createDefault('John', 'Doe', 'admin@admin.com', 'admin');

        // get first user
        $user = $this->account->users()->first();
        $user->confirmed = true;
        $user->save();
    }

    public function artisan($command, array $arguments = [])
    {
        $this->line('php artisan '.$command);
        $this->callSilent($command, $arguments);
    }
}
