<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CheckTelegramWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckTelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        URL::forceScheme('https');
        URL::forceRootUrl('https://monica.dundermifflin.com');
    }

    #[Test]
    public function it_says_telegram_calls_this_instance(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => [
            'url' => 'https://monica.dundermifflin.com/telegram/webhook',
            'pending_update_count' => 0,
        ]])]);

        $answer = new CheckTelegramWebhook()->execute();

        $this->assertSame([
            'url' => 'https://monica.dundermifflin.com/telegram/webhook',
            'ok' => true,
            'matches' => true,
            'lastError' => null,
        ], $answer);
        $this->assertDatabaseCount('telegram_webhook_registrations', 0);
    }

    #[Test]
    public function it_says_telegram_calls_somewhere_else_and_why_it_last_failed(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => [
            'url' => 'https://stamford.dundermifflin.com/telegram/webhook',
            'last_error_date' => 1790409600,
            'last_error_message' => 'Wrong response from the webhook: 403 Forbidden',
        ]])]);

        $answer = new CheckTelegramWebhook()->execute();

        $this->assertFalse($answer['matches']);
        $this->assertSame('Wrong response from the webhook: 403 Forbidden', $answer['lastError']);
    }

    #[Test]
    public function it_says_telegram_calls_nowhere(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['url' => '']])]);

        $answer = new CheckTelegramWebhook()->execute();

        $this->assertNull($answer['url']);
        $this->assertFalse($answer['matches']);
    }

    #[Test]
    public function it_says_telegram_could_not_be_reached(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $this->expectException(ValidationException::class);

        new CheckTelegramWebhook()->execute();
    }

    #[Test]
    public function it_asks_for_the_bot_token_first(): void
    {
        Http::fake();
        config(['services.telegram.token' => null]);

        $this->expectException(ValidationException::class);

        try {
            new CheckTelegramWebhook()->execute();
        } finally {
            Http::assertNothingSent();
        }
    }
}
