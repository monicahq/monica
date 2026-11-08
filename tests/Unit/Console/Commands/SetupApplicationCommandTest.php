<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\MeilisearchEngine;
use Override;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetupApplicationCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $compiledViews;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->compiledViews = sys_get_temp_dir().'/'.uniqid('compiled-views-');
        mkdir($this->compiledViews);
        config()->set('view.compiled', $this->compiledViews);
    }

    #[Override]
    protected function tearDown(): void
    {
        rmdir($this->compiledViews);

        parent::tearDown();
    }

    #[Test]
    public function it_prepares_the_checkout_and_the_database(): void
    {
        $this->artisan('monica:setup', ['--force' => true])
            ->expectsOutputToContain('Clearing the application cache')
            ->expectsOutputToContain('Bringing the database up to date')
            ->assertSuccessful();
    }

    #[Test]
    public function it_prepares_only_the_checkout_when_asked_for_the_build(): void
    {
        $this->artisan('monica:setup', ['--force' => true, '--build' => true])
            ->expectsOutputToContain('Clearing the application cache')
            ->doesntExpectOutputToContain('Bringing the database up to date')
            ->assertSuccessful();
    }

    #[Test]
    public function it_touches_only_the_database_when_asked_for_the_deploy(): void
    {
        $this->artisan('monica:setup', ['--force' => true, '--deploy' => true])
            ->expectsOutputToContain('Bringing the database up to date')
            ->doesntExpectOutputToContain('Clearing the application cache')
            ->assertSuccessful();
    }

    #[Test]
    public function it_sends_the_search_engine_the_settings_its_indexes_lost(): void
    {
        $this->useSearchEngine();

        /** @var MeilisearchEngine $engine */
        $engine = resolve(EngineManager::class)->engine('meilisearch');
        $index = $engine->index((new Contact)->searchableAs());
        $index->resetFilterableAttributes();
        $this->waitForSearchEngine();

        $this->artisan('monica:setup', ['--force' => true, '--deploy' => true])
            ->expectsOutputToContain('Sending the search engine the settings of its indexes')
            ->assertSuccessful();

        $this->waitForSearchEngine();

        $this->assertContains('vault_id', $index->getFilterableAttributes());
    }

    #[Test]
    public function it_sends_no_settings_to_an_engine_that_keeps_none(): void
    {
        $this->artisan('monica:setup', ['--force' => true, '--deploy' => true])
            ->doesntExpectOutputToContain('Sending the search engine the settings of its indexes')
            ->assertSuccessful();
    }

    #[Test]
    public function it_leaves_the_storage_link_alone_while_testing(): void
    {
        $this->artisan('monica:setup', ['--force' => true, '--build' => true])
            ->doesntExpectOutputToContain('Linking the storage folder into public')
            ->assertSuccessful();
    }

    #[Test]
    public function it_generates_the_keys_that_sign_oauth_tokens_when_there_are_none(): void
    {
        $keys = sys_get_temp_dir().'/'.uniqid('oauth-keys-');
        mkdir($keys);
        Passport::loadKeysFrom($keys);

        try {
            $this->artisan('monica:setup', ['--force' => true, '--build' => true])
                ->expectsOutputToContain('Generating the keys that sign OAuth tokens')
                ->assertSuccessful();

            $this->assertFileExists("{$keys}/oauth-private.key");
            $this->assertFileExists("{$keys}/oauth-public.key");
        } finally {
            array_map(unlink(...), glob("{$keys}/*"));
            rmdir($keys);
            Passport::$keyPath = null;
        }
    }

    #[Test]
    public function it_keeps_the_oauth_keys_the_environment_supplies(): void
    {
        config()->set('passport.private_key', 'the-key-the-environment-supplies');

        $this->artisan('monica:setup', ['--force' => true, '--build' => true])
            ->doesntExpectOutputToContain('Generating the keys that sign OAuth tokens')
            ->assertSuccessful();
    }

    #[Test]
    #[RunInSeparateProcess]
    public function it_refuses_to_run_in_production_without_being_forced(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->artisan('monica:setup')
            ->expectsConfirmation('Are you sure you want to run this command?', 'no')
            ->doesntExpectOutputToContain('Bringing the database up to date')
            ->assertFailed();
    }
}
