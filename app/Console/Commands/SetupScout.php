<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Group;
use App\Models\Note;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @codeCoverageIgnore
 */
class SetupScout extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:setup
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
            $this->scout();
        }
    }

    /**
     * Configure scout.
     *
     * @return void
     */
    protected function scout(): void
    {
        if (config('scout.driver') !== null) {
            $this->artisan('☐ Flush search engine', 'scout:flush', ['model' => Note::class, '--verbose' => true]);
            $this->artisan('☐ Flush search engine', 'scout:flush', ['model' => Contact::class, '--verbose' => true]);
            $this->artisan('☐ Flush search engine', 'scout:flush', ['model' => Group::class, '--verbose' => true]);
        }

        if (config('scout.driver') === 'meilisearch' && (config('scout.meilisearch.host')) !== '') {
            $this->artisan('☐ Creating indexes on Meilisearch', 'scout:sync-index-settings', ['--verbose' => true]);
        }

        $this->info('✓ Indexes created');
    }

    private function artisan(string $message, string $command, array $options = [])
    {
        $this->info($message);
        $this->getOutput()->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE
            ? $this->call($command, $options)
            : $this->callSilent($command, $options);
    }
}
