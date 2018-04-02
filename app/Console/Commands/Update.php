<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\ConfirmableTrait;
use App\Console\Commands\Helpers\CommandExecutor;
use App\Console\Commands\Helpers\CommandExecutorInterface;

class Update extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:update {--force} {--composer-install} {--dev}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update monica dependencies and migrations after a new release';

    /**
     * The Command Executor.
     *
     * @var CommandExecutorInterface
     */
    protected $commandExecutor;

    /**
     * Create a new command.
     *
     * @param CommandExecutorInterface
     */
    public function __construct(CommandExecutorInterface $commandExecutor = null)
    {
        $this->commandExecutor = $commandExecutor ?: new CommandExecutor($this);
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->confirmToProceed()) {
            $this->commandExecutor->artisan('✓ Resetting config cache', 'config:cache');
            try {
                $this->commandExecutor->artisan('✓ Maintenance mode: on', 'down', [
                    '--message' => 'Upgrading Monica v'.config('monica.app_version'),
                    '--retry' => '10',
                    ]);

                if ($this->option('composer-install') === true) {
                    $this->commandExecutor->exec('✓ Updating composer dependencies', 'composer install --no-interaction --no-suggest --ignore-platform-reqs'.($this->option('composer-install') === false ? '--no-dev' : ''));
                }

                $this->commandExecutor->artisan('✓ Performing migrations', 'migrate', ['--force' => 'true']);

                if (DB::table('activity_types')->count() == 0) {
                    $this->commandExecutor->artisan('✓ Filling the Activity Types table', 'db:seed', ['--class' => 'ActivityTypesTableSeeder', '--force' => 'true']);
                }
                if (DB::table('countries')->count() == 0) {
                    $this->commandExecutor->artisan('✓ Filling the Countries table', 'db:seed', ['--class' => 'CountriesSeederTable', '--force' => 'true']);
                }
                if (! file_exists(public_path('storage'))) {
                    $this->commandExecutor->artisan('✓ Symlink the storage folder', 'storage:link');
                }
            } finally {
                $this->commandExecutor->artisan('✓ Maintenance mode: off', 'up');
            }

            $this->line('Monica v'.config('monica.app_version').' is set up, enjoy.');
        }
    }
}
