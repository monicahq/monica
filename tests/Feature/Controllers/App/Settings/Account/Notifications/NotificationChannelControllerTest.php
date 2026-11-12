<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Notifications;

use App\Enums\NotificationChannelEnum;
use App\Models\Account;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationChannelControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_every_channel_and_what_each_one_still_needs(): void
    {
        $this->signedIn();

        $response = $this->get(route('settings.notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('Telegram');
        $response->assertSee('Text message');
        $response->assertSee('Nothing to type. Following the link opens a conversation with our bot, and the bot tells us where to send.');
    }

    #[Test]
    public function it_saves_a_destination_and_a_credential(): void
    {
        $user = $this->signedIn();

        $this->channel($user, NotificationChannelEnum::Sms)->fill(['proven_at' => now(), 'destination' => '+15705550172'])->save();

        $response = $this->put(route('settings.notifications.update', 'sms'), [
            'is_enabled' => '1',
            'destination' => '+15705550172',
        ]);

        $response->assertRedirect(route('settings.notifications.index'));
        $response->assertSessionHas('status', 'Your notification settings are saved.');

        $channel = $this->channel($user, NotificationChannelEnum::Sms);

        $this->assertTrue($channel->is_enabled);
        $this->assertSame('+15705550172', $channel->destination);
    }

    #[Test]
    public function it_says_when_the_channel_being_switched_off_is_the_last_one_that_works(): void
    {
        $user = $this->signedIn();
        NotificationChannel::query()
            ->where('account_id', $user->account_id)
            ->where('channel', '!=', NotificationChannelEnum::Email->value)
            ->update(['is_enabled' => false]);

        $response = $this->get(route('settings.notifications.index'));

        $response->assertSee('This is the only channel that still works. Switching it off means nothing reaches you at all.');
    }

    #[Test]
    public function it_refuses_to_switch_on_a_channel_this_server_cannot_send_on(): void
    {
        config(['vonage.api_key' => null]);
        $user = $this->signedIn();
        $this->channel($user, NotificationChannelEnum::Sms)->fill([
            'destination' => '+15705550172',
            'proven_at' => now(),
        ])->save();

        $response = $this->put(route('settings.notifications.update', 'sms'), [
            'is_enabled' => '1',
            'destination' => '+15705550172',
        ]);

        $response->assertSessionHasErrors(['channel' => 'This server has not been set up to send on this channel.']);
        $this->assertFalse($this->channel($user, NotificationChannelEnum::Sms)->is_enabled);
    }

    #[Test]
    public function connecting_telegram_sends_you_to_the_bot_with_a_token(): void
    {
        $user = $this->signedIn();

        $response = $this->post(route('settings.notifications.connection.store', 'telegram'));

        $channel = $this->channel($user, NotificationChannelEnum::Telegram);

        $this->assertNotNull($channel->verification_token_hash);

        $token = Str::after((string) $response->headers->get('Location'), '?start=');

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{64}$/', $token);
        $this->assertSame(hash('sha256', $token), $channel->verification_token_hash);
    }

    #[Test]
    public function a_conversation_identifier_cannot_be_typed_in_from_here(): void
    {
        $user = $this->signedIn();

        $response = $this->put(route('settings.notifications.update', 'telegram'), [
            'is_enabled' => '0',
            'destination' => '999999999',
        ]);

        $response->assertSessionHasErrors(['channel' => 'This channel is told where to send by the service, not from here.']);
        $this->assertNull($this->channel($user, NotificationChannelEnum::Telegram)->destination);
    }

    #[Test]
    public function the_screen_works_with_no_integration_installed_at_all(): void
    {
        config(['services.telegram.installed' => false, 'services.vonage.installed' => false]);
        $this->signedIn();

        $response = $this->get(route('settings.notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('In the app');
        $response->assertSee('Email');
        $response->assertDontSee('Telegram');
        $response->assertDontSee('Text message');
        $response->assertDontSee('Connect Telegram');
        $response->assertDontSee('id="sms-destination"', escape: false);
    }

    #[Test]
    public function an_unknown_channel_is_not_found(): void
    {
        $this->signedIn();

        $this->put(route('settings.notifications.update', 'carrier-pigeon'), ['is_enabled' => '0'])->assertStatus(404);
    }

    #[Test]
    public function it_answers_not_found_for_text_messages_while_sms_notifications_is_off_for_the_account(): void
    {
        $user = $this->signedIn();
        $user->account->features()->deactivate('sms-notifications');
        Feature::flushCache();

        $response = $this->put(route('settings.notifications.update', 'sms'), [
            'is_enabled' => '1',
            'destination' => '+15705550172',
        ]);

        $response->assertNotFound();
    }

    private function signedIn(): User
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $this->actingAs($user);

        return $user;
    }

    private function channel(User $user, NotificationChannelEnum $channel): NotificationChannel
    {
        return NotificationChannel::query()
            ->where('account_id', $user->account_id)
            ->where('channel', $channel->value)
            ->firstOrFail();
    }
}
