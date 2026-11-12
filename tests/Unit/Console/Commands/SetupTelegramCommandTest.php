<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetupTelegramCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Http::preventStrayRequests();
        URL::forceScheme('https');
        URL::forceRootUrl('https://monica.dundermifflin.com');
    }

    #[Test]
    public function it_registers_the_bot_on_behalf_of_nobody(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => true])]);

        $this->artisan('monica:setup-telegram')
            ->expectsOutputToContain('Registered https://monica.dundermifflin.com/telegram/webhook with Telegram.')
            ->assertSuccessful();

        $this->assertDatabaseHas('telegram_webhook_registrations', ['user_id' => null]);
    }

    #[Test]
    public function it_says_what_is_missing(): void
    {
        Http::fake();
        config(['services.telegram.webhook_secret' => null]);

        $this->artisan('monica:setup-telegram')
            ->assertFailed();

        Http::assertNothingSent();
    }

    #[Test]
    public function it_repeats_what_telegram_refused(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad Request: bad webhook'], 400)]);

        $this->artisan('monica:setup-telegram')
            ->expectsOutputToContain('Bad Request: bad webhook')
            ->assertFailed();

        $this->assertDatabaseCount('telegram_webhook_registrations', 0);
    }
}
