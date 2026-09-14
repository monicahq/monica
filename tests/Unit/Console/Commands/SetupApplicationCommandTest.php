<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Override;
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
    public function it_leaves_the_storage_link_alone_while_testing(): void
    {
        $this->artisan('monica:setup', ['--force' => true, '--build' => true])
            ->doesntExpectOutputToContain('Linking the storage folder into public')
            ->assertSuccessful();
    }

    #[Test]
    public function it_refuses_to_run_in_production_without_being_forced(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->artisan('monica:setup')
            ->expectsConfirmation('Are you sure you want to run this command?', 'no')
            ->doesntExpectOutputToContain('Bringing the database up to date')
            ->assertFailed();
    }
}
