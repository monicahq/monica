<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Notifications;

use App\Enums\InstanceSettingEnum;
use App\Enums\NotificationChannelEnum;
use App\Helpers\InstanceSettings;
use App\Models\Account;
use App\Models\InstanceSetting;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TelegramWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_the_conversation_and_switches_the_channel_on(): void
    {
        $channel = $this->awaitingConnection('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');

        $response = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'testing-webhook-secret')
            ->postJson(route('telegram.webhook'), $this->update('/start '.str_repeat('a', 64), 918273645));

        $response->assertOk();
        $response->assertJsonPath('status', 'connected');

        $channel->refresh();

        $this->assertSame('918273645', $channel->destination);
        $this->assertNotNull($channel->proven_at);
        $this->assertNull($channel->verification_token_hash);
        $this->assertTrue($channel->is_enabled);
    }

    #[Test]
    public function it_refuses_a_call_that_does_not_carry_telegrams_secret(): void
    {
        $channel = $this->awaitingConnection('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'somebody-elses-guess')
            ->postJson(route('telegram.webhook'), $this->update('/start '.str_repeat('a', 64), 918273645))
            ->assertForbidden();

        $this->assertNull($channel->refresh()->destination);
    }

    #[Test]
    public function a_token_nobody_is_waiting_on_is_not_found(): void
    {
        $this->awaitingConnection('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'testing-webhook-secret')
            ->postJson(route('telegram.webhook'), $this->update('/start '.str_repeat('c', 64), 918273645))
            ->assertNotFound();
    }

    #[Test]
    public function ordinary_chatter_in_the_conversation_is_ignored(): void
    {
        $channel = $this->awaitingConnection('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');

        foreach (['hello there', '/start', '/start not-a-token', '/help '.str_repeat('a', 64)] as $text) {
            $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'testing-webhook-secret')
                ->postJson(route('telegram.webhook'), $this->update($text, 918273645))
                ->assertStatus(202);
        }

        $this->assertNull($channel->refresh()->destination);
    }

    #[Test]
    public function a_token_is_spent_once_so_a_replayed_call_finds_nothing(): void
    {
        $this->awaitingConnection('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        $payload = $this->update('/start '.str_repeat('a', 64), 918273645);

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'testing-webhook-secret')
            ->postJson(route('telegram.webhook'), $payload)->assertOk();

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'testing-webhook-secret')
            ->postJson(route('telegram.webhook'), $payload)->assertNotFound();
    }

    #[Test]
    public function it_connects_the_account_the_token_belongs_to_and_no_other(): void
    {
        $mine = $this->awaitingConnection('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        $theirs = $this->awaitingConnection('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb');

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'testing-webhook-secret')
            ->postJson(route('telegram.webhook'), $this->update('/start '.str_repeat('a', 64), 918273645))
            ->assertOk();

        $this->assertSame('918273645', $mine->refresh()->destination);
        $this->assertNull($theirs->refresh()->destination);
    }

    #[Test]
    public function it_answers_as_not_found_while_no_token_is_in_effect(): void
    {
        $channel = $this->awaitingConnection(str_repeat('a', 64));
        config(['services.telegram.token' => null]);

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'testing-webhook-secret')
            ->postJson(route('telegram.webhook'), $this->update('/start '.str_repeat('a', 64), 918273645))
            ->assertNotFound();

        $this->assertNull($channel->refresh()->destination);
    }

    #[Test]
    public function it_accepts_a_token_saved_on_the_configuration_screen(): void
    {
        $channel = $this->awaitingConnection(str_repeat('a', 64));
        config(['services.telegram.token' => null, 'instance_deployment.telegram_bot_token' => null]);
        InstanceSetting::factory()->create(['name' => InstanceSettingEnum::TelegramBotToken->value, 'value' => 'token-saved-by-michael']);
        InstanceSettings::apply();

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'testing-webhook-secret')
            ->postJson(route('telegram.webhook'), $this->update('/start '.str_repeat('a', 64), 918273645))
            ->assertOk();

        $this->assertSame('918273645', $channel->refresh()->destination);
    }

    #[Test]
    public function it_refuses_a_call_when_no_secret_is_configured(): void
    {
        $channel = $this->awaitingConnection(str_repeat('a', 64));
        config(['services.telegram.webhook_secret' => null]);

        $this->postJson(route('telegram.webhook'), $this->update('/start '.str_repeat('a', 64), 918273645))
            ->assertForbidden();

        $this->assertNull($channel->refresh()->destination);
    }

    /** @return array<string, mixed> */
    private function update(string $text, int $chatId): array
    {
        return [
            'update_id' => 1,
            'message' => [
                'message_id' => 1,
                'text' => $text,
                'chat' => ['id' => $chatId, 'type' => 'private'],
            ],
        ];
    }

    private function awaitingConnection(string $token): NotificationChannel
    {
        $account = Account::factory()->create();
        User::factory()->create(['account_id' => $account->id]);

        $channel = NotificationChannel::query()
            ->where('account_id', $account->id)
            ->where('channel', NotificationChannelEnum::Telegram->value)
            ->firstOrFail();

        $channel->fill([
            'verification_token_hash' => hash('sha256', $token),
            'proof_expires_at' => now()->addMinutes(10),
        ])->save();

        return $channel;
    }
}
