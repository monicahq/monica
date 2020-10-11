<?php

namespace App\Console\Commands\Tests;

use App\Models\User\User;
use Illuminate\Console\Command;
use App\Services\User\AcceptPolicy;

class SetupFrontEndTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:frontendtestuser {--database= : The database connection to use}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a user exclusively for front-end testing with Cypress.';

    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new command.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->migrator = app('migrator');
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->migrator->setConnection($this->option('database'));

        $user = factory(User::class)->create();
        $user->account->populateDefaultFields();

        app(AcceptPolicy::class)->execute([
            'account_id' => $user->account->id,
            'user_id' => $user->id,
            'ip_address' => null,
        ]);

        $this->info($user->getKey());
    }
}
