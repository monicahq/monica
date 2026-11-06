<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;
use Laravel\Scout\Contracts\UpdatesIndexSettings;
use Laravel\Scout\EngineManager;

#[Signature(<<<'EOF'
monica:setup
    {--force : Run without asking, which is what a container has to do}
    {--build : Run only the steps that prepare the checkout}
    {--deploy : Run only the steps that touch the database}
EOF)]
#[Description('Prepare the application to run, and bring the database up to date')]
class SetupApplicationCommand extends Command
{
    use ConfirmableTrait;

    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        if ($this->runs('build')) {
            $this->linkStorage();
            $this->generateOAuthKeys();
            $this->clearApplicationCache();
            $this->resetCompiledFiles();
            $this->cacheConfiguration();
        }

        if ($this->runs('deploy')) {
            $this->migrate();
            $this->syncSearchIndexSettings();
        }

        return self::SUCCESS;
    }

    private function runs(string $phase): bool
    {
        if ($this->option('build') === false && $this->option('deploy') === false) {
            return true;
        }

        return $this->option($phase) === true;
    }

    private function linkStorage(): void
    {
        if ($this->getLaravel()->environment() === 'testing') {
            return;
        }

        if (file_exists(public_path('storage'))) {
            return;
        }

        $this->artisan('Linking the storage folder into public', 'storage:link');
    }

    /**
     * The keys sign the tokens an assistant holds, so they are made once and kept. Replacing them
     * would disconnect every assistant, which is why an existing pair, or a pair the environment
     * supplies, is left alone.
     */
    private function generateOAuthKeys(): void
    {
        if (config('passport.private_key') !== null) {
            return;
        }

        if (file_exists(Passport::keyPath('oauth-private.key'))) {
            return;
        }

        $this->artisan('Generating the keys that sign OAuth tokens', 'passport:keys');
    }

    private function clearApplicationCache(): void
    {
        if (! $this->cacheStoreAnswers()) {
            return;
        }

        $this->artisan('Clearing the application cache', 'cache:clear');
    }

    private function resetCompiledFiles(): void
    {
        $this->artisan('Clearing the compiled configuration', 'config:clear');

        if ($this->getLaravel()->environment() === 'production') {
            $this->artisan('Compiling the routes', 'route:cache');
            $this->artisan('Compiling the views', 'view:cache');
            $this->artisan('Compiling the events', 'event:cache');

            return;
        }

        $this->artisan('Clearing the compiled routes', 'route:clear');
        $this->artisan('Clearing the compiled views', 'view:clear');
        $this->artisan('Clearing the compiled events', 'event:clear');
    }

    private function cacheConfiguration(): void
    {
        if ($this->getLaravel()->environment() !== 'production') {
            return;
        }

        if (! $this->cacheStoreAnswers()) {
            return;
        }

        $this->artisan('Compiling the configuration', 'config:cache');
    }

    private function migrate(): void
    {
        $this->artisan('Bringing the database up to date', 'migrate', ['--force' => true]);
    }

    /**
     * An engine creates an index without any settings the first time a record reaches it, and such an
     * index cannot filter by vault, so every deploy sends the settings again. Scout reports an engine
     * that does not answer without failing, so a search outage never stops a deploy.
     */
    private function syncSearchIndexSettings(): void
    {
        if (! resolve(EngineManager::class)->engine() instanceof UpdatesIndexSettings) {
            return;
        }

        $this->artisan('Sending the search engine the settings of its indexes', 'scout:sync-index-settings');
    }

    private function cacheStoreAnswers(): bool
    {
        if (config('cache.default') !== 'database') {
            return true;
        }

        return Schema::hasTable(config('cache.stores.database.table'));
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function artisan(string $message, string $command, array $options = []): void
    {
        $this->components->info($message);

        if ($this->getOutput()->isVerbose()) {
            $this->call($command, $options);

            return;
        }

        $this->callSilent($command, $options);
    }
}
