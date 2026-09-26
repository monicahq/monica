<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Models\TelegramWebhookRegistration;
use App\ViewModels\Admin\TelegramRegistrationPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TelegramRegistrationPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceScheme('https');
        URL::forceRootUrl('https://erica.dundermifflin.com');
    }

    #[Test]
    public function it_says_the_bot_was_never_registered(): void
    {
        $panel = new TelegramRegistrationPanel;

        $this->assertSame('unregistered', $panel->state());
        $this->assertSame('The bot is not registered with Telegram yet.', $panel->statusSentence());
    }

    #[Test]
    public function it_says_when_and_where_the_bot_was_registered(): void
    {
        TelegramWebhookRegistration::factory()->current()->create(['registered_at' => '2026-09-26 10:00:00']);

        $panel = new TelegramRegistrationPanel;

        $this->assertSame('registered', $panel->state());
        $this->assertSame('Registered on September 26, 2026 for https://erica.dundermifflin.com/telegram/webhook.', $panel->statusSentence());
    }

    #[Test]
    public function it_says_why_the_registration_is_out_of_date(): void
    {
        TelegramWebhookRegistration::factory()->current()->create(['registered_at' => '2026-09-26 10:00:00']);
        config(['services.telegram.webhook_secret' => 'a-secret-dwight-chose']);

        $panel = new TelegramRegistrationPanel;

        $this->assertSame('outOfDate', $panel->state());
        $this->assertSame('Registered on September 26, 2026, but out of date because the secret changed. Register it again.', $panel->statusSentence());
    }

    #[Test]
    public function it_keeps_the_control_unavailable_while_something_is_missing(): void
    {
        config(['services.telegram.token' => null]);

        $this->assertSame('Set the bot token first.', new TelegramRegistrationPanel()->unavailableReason());
    }

    #[Test]
    public function it_warns_before_a_secret_the_registration_relies_on_is_replaced(): void
    {
        TelegramWebhookRegistration::factory()->current()->create();

        $this->assertSame(
            'Telegram stops reaching this instance until the bot is registered again.',
            new TelegramRegistrationPanel()->secretReplacementWarning(),
        );
    }

    #[Test]
    public function it_does_not_warn_before_a_secret_is_generated_for_a_bot_nobody_registered(): void
    {
        $this->assertNull(new TelegramRegistrationPanel()->secretReplacementWarning());
    }
}
