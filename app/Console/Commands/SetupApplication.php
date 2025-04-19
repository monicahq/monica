<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @codeCoverageIgnore
 */
#[AsCommand(name: 'monica:setup')]
class SetupApplication extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:setup
                            {--force : Force the operation to run when in production.}
                            {--skip-storage-link : Skip storage link create.}
                            {--skip-docs : Skip api docs generation.}
                            {--build : Run only build commands.}
                            {--deploy : Run only deploy commands.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install or update the application, and run migrations after a new release';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if ($this->confirmToProceed()) {
            if ($this->option('build') === true || ($this->option('build') === false && $this->option('deploy') === false)) {
                $this->symlink();
                $this->resetCache();
                $this->clearConfig();
                $this->documentation();
                $this->cacheConfig();
            }
            if ($this->option('deploy') === true || ($this->option('build') === false && $this->option('deploy') === false)) {
                $this->migrate();
                $this->scout();
            }
        }
    }

    /**
     * Clear or rebuild all cache.
     */
    protected function resetCache(): void
    {
        if (config('cache.default') != 'database' || Schema::hasTable(config('cache.stores.database.table'))) {
            $this->artisan('✓ Resetting application cache', 'cache:clear');
        }
    }

    /**
     * Clear configuration.
     */
    protected function clearConfig(): void
    {
        if ($this->getLaravel()->environment() == 'production') {
            $this->artisan('✓ Clear config cache', 'config:clear');
            $this->artisan('✓ Resetting route cache', 'route:cache');
            $this->artisan('✓ Resetting view cache', 'view:cache');
            $this->artisan('✓ Resetting event cache', 'event:cache');
        } else {
            $this->artisan('✓ Clear config cache', 'config:clear');
            $this->artisan('✓ Clear route cache', 'route:clear');
            $this->artisan('✓ Clear view cache', 'view:clear');
            $this->artisan('✓ Clear event cache', 'event:clear');
        }
    }

    /**
     * Cache configuration.
     */
    protected function cacheConfig(): void
    {
        // Cache config
        if ($this->getLaravel()->environment() == 'production'
            && (config('cache.default') != 'database' || Schema::hasTable(config('cache.stores.database.table')))) {
            $this->artisan('✓ Cache configuraton', 'config:cache');
        }
    }

    /**
     * Symlink storage to public.
     */
    protected function symlink(): void
    {
        if ($this->option('skip-storage-link') !== true
            && $this->getLaravel()->environment() != 'testing'
            && ! file_exists(public_path('storage'))) {
            $this->artisan('✓ Symlink the storage folder', 'storage:link');
        }
    }

    /**
     * Run migrations.
     */
    protected function migrate(): void
    {
        $this->artisan('✓ Performing migrations', 'migrate', ['--force' => true]);
    }

    /**
     * Configure scout.
     */
    protected function scout(): void
    {
        $this->artisan('✓ Setup scout', 'scout:setup', ['--force' => true]);
    }

    /**
     * Regenerate api documentation.
     */
    protected function documentation(): void
    {
        if ($this->option('skip-docs') !== true) {
            $this->artisan('✓ Generate api documentation', 'scribe:setup', ['--clean' => true, '--force' => true]);
        }
    }

    private function artisan(string $message, string $command, array $options = [])
    {
        $this->info($message);
        $this->getOutput()->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE
            ? $this->call($command, $options)
            : $this->callSilent($command, $options);
    }
}
