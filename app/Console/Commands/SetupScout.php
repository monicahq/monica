<?php

namespace App\Console\Commands;

use App\Helpers\ScoutHelper;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @codeCoverageIgnore
 */
#[AsCommand(name: 'scout:setup')]
class SetupScout extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:setup
                            {--flush : Flush the indexes.}
                            {--import : Import the models into the search index.}
                            {--force : Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup scout indexes.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if ($this->confirmToProceed()) {
            $this->scoutConfigure();
            $this->scoutFlush();
            $this->scoutImport();
        }
    }

    /**
     * Configure scout.
     */
    protected function scoutConfigure(): void
    {
        if (ScoutHelper::isIndexed()) {
            $this->artisan('☐ Updating indexes', 'scout:sync-index-settings', ['--verbose' => true]);
        }
    }

    /**
     * Flush indexes.
     */
    protected function scoutFlush(): void
    {
        if ($this->option('flush') && ScoutHelper::isIndexed()) {
            // Using meilisearch config for any driver
            foreach (config('scout.meilisearch.index-settings') as $index => $settings) {
                $name = (new $index)->getTable();
                $this->artisan("☐ Flush {$name} index", 'scout:flush', ['model' => $index, '--verbose' => true]);
            }

            $this->info('✓ Indexes flushed');
        }
    }

    /**
     * Import models.
     */
    protected function scoutImport(): void
    {
        if ($this->option('import') && ScoutHelper::isIndexed()) {
            // Using meilisearch config for any driver
            foreach (config('scout.meilisearch.index-settings') as $index => $settings) {
                $name = (new $index)->getTable();
                $this->artisan("☐ Import {$name}", 'scout:import', ['model' => $index, '--verbose' => true]);
            }

            $this->info('✓ Indexes imported');
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
