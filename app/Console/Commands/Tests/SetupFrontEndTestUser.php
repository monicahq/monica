<?php

namespace App\Console\Commands\Tests;

use App\Models\User\User;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use App\Console\Commands\Helpers\CommandExecutor;
use App\Console\Commands\Helpers\CommandExecutorInterface;

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
     * The Command Executor.
     *
     * @var CommandExecutorInterface
     */
    public $commandExecutor;

    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new command.
     *
     * @param  \Illuminate\Database\Migrations\Migrator  $migrator
     */
    public function __construct()
    {
        $this->commandExecutor = new CommandExecutor($this);
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
        $user->acceptPolicy();

        $this->info($user->getKey());
    }
}
