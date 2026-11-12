<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\TelegramWebhook;
use App\Models\TelegramWebhookRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceScheme('https');
        URL::forceRootUrl('https://monica.dundermifflin.com');
    }

    #[Test]
    public function it_lets_the_bot_be_registered_once_everything_is_in_place(): void
    {
        $this->assertNull(TelegramWebhook::missingPrerequisite());
    }

    #[Test]
    public function it_refuses_an_address_over_plain_http(): void
    {
        URL::forceScheme('http');
        URL::forceRootUrl('http://monica.dundermifflin.com');

        $this->assertStringContainsString('http://monica.dundermifflin.com/telegram/webhook', (string) TelegramWebhook::missingPrerequisite());
    }

    #[Test]
    public function it_refuses_a_development_host(): void
    {
        URL::forceRootUrl('https://monica.test');

        $this->assertNotNull(TelegramWebhook::missingPrerequisite());

        URL::forceRootUrl('https://localhost');

        $this->assertNotNull(TelegramWebhook::missingPrerequisite());
    }

    #[Test]
    public function it_is_current_when_nothing_changed(): void
    {
        $registration = TelegramWebhookRegistration::factory()->current()->create();

        $this->assertSame([], TelegramWebhook::outOfDateReasons($registration));
        $this->assertTrue(TelegramWebhook::isCurrent($registration));
    }

    #[Test]
    public function it_is_not_current_when_the_bot_was_never_registered(): void
    {
        $this->assertFalse(TelegramWebhook::isCurrent(null));
    }

    #[Test]
    public function it_notices_the_instance_address_changed(): void
    {
        $registration = TelegramWebhookRegistration::factory()->current()->create();

        URL::forceRootUrl('https://scranton.dundermifflin.com');

        $this->assertFalse(TelegramWebhook::isCurrent($registration));
    }
}
