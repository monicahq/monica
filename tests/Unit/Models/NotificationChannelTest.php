<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\NotificationChannelEnum;
use App\Models\Account;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationChannelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_channel_that_asks_for_nothing_is_set_up_from_the_start(): void
    {
        $channel = NotificationChannel::factory()->channel(NotificationChannelEnum::Email)->make();

        $this->assertTrue($channel->isSetUp());
        $this->assertTrue($channel->isProven());
        $this->assertTrue($channel->isReady());
    }

    #[Test]
    public function a_channel_missing_its_destination_is_not_set_up(): void
    {
        $channel = NotificationChannel::factory()->channel(NotificationChannelEnum::Telegram)->make([
            'destination' => null,
        ]);

        $this->assertFalse($channel->isSetUp());
        $this->assertFalse($channel->isReady());
    }

    #[Test]
    public function a_text_message_stays_unproven_until_a_code_has_been_typed_back(): void
    {
        $channel = NotificationChannel::factory()->channel(NotificationChannelEnum::Sms)->unproven()->make();

        $this->assertTrue($channel->isSetUp());
        $this->assertFalse($channel->isProven());
        $this->assertFalse($channel->isReady());

        $channel->proven_at = now();

        $this->assertTrue($channel->isProven());
        $this->assertTrue($channel->isReady());
    }

    #[Test]
    public function a_text_message_is_not_ready_on_a_server_with_no_provider(): void
    {
        config(['vonage.api_key' => null]);

        $channel = NotificationChannel::factory()->channel(NotificationChannelEnum::Sms)->enabled()->make();

        $this->assertTrue($channel->isSetUp());
        $this->assertTrue($channel->isProven());
        $this->assertFalse($channel->isReady());
        $this->assertFalse($channel->mayCarry());
    }

    #[Test]
    public function only_an_enabled_and_ready_channel_carries_anything(): void
    {
        $ready = NotificationChannel::factory()->channel(NotificationChannelEnum::Telegram)->enabled()->make();
        $off = NotificationChannel::factory()->channel(NotificationChannelEnum::Email)->make(['is_enabled' => false]);
        $incomplete = NotificationChannel::factory()->channel(NotificationChannelEnum::Telegram)->make(['is_enabled' => true]);

        $this->assertTrue($ready->mayCarry());
        $this->assertFalse($off->mayCarry());
        $this->assertFalse($incomplete->mayCarry());
    }

    #[Test]
    public function it_is_reached_by_the_channel_name_and_only_inside_the_account(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        $mine = $this->row($account, NotificationChannelEnum::Telegram);
        $theirs = $this->row(Account::factory()->create(), NotificationChannelEnum::Telegram);

        $this->actingAs($user);

        $this->assertSame($mine->id, new NotificationChannel()->resolveRouteBinding('telegram')?->id);
        $this->assertNotSame($theirs->id, new NotificationChannel()->resolveRouteBinding('telegram')?->id);
        $this->assertNull(new NotificationChannel()->resolveRouteBinding('carrier-pigeon'));
    }

    #[Test]
    public function text_messages_are_neither_offered_nor_carried_while_sms_notifications_is_off_for_the_account(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        $sms = $this->row($account, NotificationChannelEnum::Sms);
        $sms->update(['is_enabled' => true, 'destination' => '+15705550100', 'proven_at' => now()]);

        $this->assertTrue($sms->isOffered());
        $this->assertTrue($sms->mayCarry());

        $account->features()->deactivate('sms-notifications');
        Feature::flushCache();
        $this->actingAs($user);

        $this->assertFalse($sms->isOffered());
        $this->assertFalse($sms->mayCarry());
        $this->assertTrue($this->row($account, NotificationChannelEnum::Email)->isOffered());
        $this->assertNull(new NotificationChannel()->resolveRouteBinding('sms'));
        $this->assertNotNull(new NotificationChannel()->resolveRouteBinding('email'));
    }

    #[Test]
    public function a_channel_nobody_has_set_up_holds_no_setup(): void
    {
        $channel = NotificationChannel::factory()->channel(NotificationChannelEnum::Sms)->make();

        $this->assertFalse($channel->holdsSetup());
    }

    #[Test]
    public function a_number_is_a_setup(): void
    {
        $channel = NotificationChannel::factory()->channel(NotificationChannelEnum::Sms)->unproven()->make();

        $this->assertTrue($channel->holdsSetup());
    }

    #[Test]
    public function a_code_waiting_to_be_typed_back_is_a_setup(): void
    {
        $channel = NotificationChannel::factory()->channel(NotificationChannelEnum::Sms)->make([
            'proof_code_hash' => 'a hash',
        ]);

        $this->assertTrue($channel->holdsSetup());
    }

    #[Test]
    public function a_telegram_connection_in_progress_is_a_setup(): void
    {
        $channel = NotificationChannel::factory()->channel(NotificationChannelEnum::Telegram)->make([
            'verification_token_hash' => hash('sha256', 'dunder-mifflin'),
        ]);

        $this->assertTrue($channel->holdsSetup());
    }

    /**
     * The row an account already has for a channel. Every account is given one
     * per channel when it is made, so a test never creates one of its own.
     */
    private function row(Account $account, NotificationChannelEnum $channel): NotificationChannel
    {
        return NotificationChannel::query()
            ->where('account_id', $account->id)
            ->where('channel', $channel->value)
            ->firstOrFail();
    }
}
