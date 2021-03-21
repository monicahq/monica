<?php

namespace App\Console\Commands;

use App\Helpers\DBHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Console\ConfirmableTrait;

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
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        if ($this->confirmToProceed()) {
            try {
                $this->artisan('✓ Maintenance mode: on', 'down', [
                    '--retry' => '10',
                ]);

                // Clear or rebuild all cache
                if (config('cache.default') != 'database' || Schema::hasTable(config('cache.stores.database.table'))) {
                    $this->artisan('✓ Resetting application cache', 'cache:clear');
                }

                if ($this->getLaravel()->environment() == 'production') {
                    $this->artisan('✓ Clear config cache', 'config:clear');
                    $this->artisan('✓ Resetting route cache', 'route:cache');
                    if ($this->getLaravel()->version() > '5.6') {
                        $this->artisan('✓ Resetting view cache', 'view:cache');
                    } else {
                        $this->artisan('✓ Resetting view cache', 'view:clear');
                    }
                } else {
                    $this->artisan('✓ Clear config cache', 'config:clear');
                    $this->artisan('✓ Clear route cache', 'route:clear');
                    $this->artisan('✓ Clear view cache', 'view:clear');
                }

                if ($this->option('composer-install') === true) {
                    $this->exec('✓ Updating composer dependencies', 'composer install --no-interaction'.($this->option('dev') === false ? ' --no-dev' : ''));
                }

                if ($this->option('skip-storage-link') !== true && $this->getLaravel()->environment() != 'testing' && ! file_exists(public_path('storage'))) {
                    $this->artisan('✓ Symlink the storage folder', 'storage:link');
                }

                if ($this->migrateCollationTest()) {
                    $this->artisan('✓ Performing collation migrations', 'migrate:collation', ['--force']);
                }

                $this->artisan('✓ Performing migrations', 'migrate', ['--force']);

                $this->artisan('✓ Check for encryption keys', 'monica:passport', ['--force']);

                $this->artisan('✓ Ping for new version', 'monica:ping', ['--force']);

                // Cache config
                if ($this->getLaravel()->environment() == 'production'
                    && (config('cache.default') != 'database' || Schema::hasTable(config('cache.stores.database.table')))) {
                    $this->artisan('✓ Cache configuraton', 'config:cache');
                }
            } finally {
                $this->artisan('✓ Maintenance mode: off', 'up');
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
