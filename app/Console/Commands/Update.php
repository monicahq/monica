<?php

namespace App\Console\Commands;

use App\Helpers\DBHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
    protected $signature = 'monica:update
                            {--force : Force the operation to run when in production.}
                            {--composer-install : Updating composer dependencies.}
                            {--skip-storage-link : Skip storage link create.}
                            {--dev : Install dev dependencies too.}';

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
    public $commandExecutor;

    /**
     * Create a new command.
     */
    public function __construct()
    {
        $this->commandExecutor = new CommandExecutor($this);
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        if ($this->confirmToProceed()) {
            try {
                $this->commandExecutor->artisan('✓ Maintenance mode: on', 'down', [
                    '--message' => 'Upgrading Monica v'.config('monica.app_version'),
                    '--retry' => '10',
                ]);

                // Clear or rebuild all cache
                if (config('cache.default') != 'database' || Schema::hasTable(config('cache.stores.database.table'))) {
                    $this->commandExecutor->artisan('✓ Resetting application cache', 'cache:clear');
                }

                if ($this->getLaravel()->environment() == 'production') {
                    $this->commandExecutor->artisan('✓ Clear config cache', 'config:clear');
                    $this->commandExecutor->artisan('✓ Resetting route cache', 'route:cache');
                    if ($this->getLaravel()->version() > '5.6') {
                        $this->commandExecutor->artisan('✓ Resetting view cache', 'view:cache');
                    } else {
                        $this->commandExecutor->artisan('✓ Resetting view cache', 'view:clear');
                    }
                } else {
                    $this->commandExecutor->artisan('✓ Clear config cache', 'config:clear');
                    $this->commandExecutor->artisan('✓ Clear route cache', 'route:clear');
                    $this->commandExecutor->artisan('✓ Clear view cache', 'view:clear');
                }

                if ($this->option('composer-install') === true) {
                    $this->commandExecutor->exec('✓ Updating composer dependencies', 'composer install --no-interaction --no-suggest --ignore-platform-reqs'.($this->option('dev') === false ? ' --no-dev' : ''));
                }

                if ($this->option('skip-storage-link') !== true && $this->getLaravel()->environment() != 'testing' && ! file_exists(public_path('storage'))) {
                    $this->commandExecutor->artisan('✓ Symlink the storage folder', 'storage:link');
                }

                if ($this->migrateCollationTest()) {
                    $this->commandExecutor->artisan('✓ Performing collation migrations', 'migrate:collation', ['--force']);
                }

                $this->commandExecutor->artisan('✓ Performing migrations', 'migrate', ['--force']);

                $this->commandExecutor->artisan('✓ Check for encryption keys', 'monica:passport', ['--force']);

                $this->commandExecutor->artisan('✓ Ping for new version', 'monica:ping', ['--force']);

                // Cache config
                if ($this->getLaravel()->environment() == 'production'
                    && (config('cache.default') != 'database' || Schema::hasTable(config('cache.stores.database.table')))) {
                    $this->commandExecutor->artisan('✓ Cache configuraton', 'config:cache');
                }
            } finally {
                $this->commandExecutor->artisan('✓ Maintenance mode: off', 'up');
            }

            $this->line('Monica v'.config('monica.app_version').' is set up, enjoy.');
        }
    }

    private function migrateCollationTest()
    {
        $connection = DBHelper::connection();

        if ($connection->getDriverName() != 'mysql') {
            return false;
        }

        $databasename = $connection->getDatabaseName();

        $schemata = DB::select(
            'select DEFAULT_CHARACTER_SET_NAME from information_schema.schemata where schema_name = ?',
            [$databasename]
        );

        $schema = $schemata[0]->DEFAULT_CHARACTER_SET_NAME;

        return config('database.use_utf8mb4') && $schema == 'utf8'
            || ! config('database.use_utf8mb4') && $schema == 'utf8mb4';
    }
}
