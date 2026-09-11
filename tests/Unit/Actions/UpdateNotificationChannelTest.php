<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateNotificationChannel;
use App\Enums\NotificationChannelEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ChannelIsNotValid;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateNotificationChannelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_saves_a_destination_and_switches_the_channel_on(): void
    {
        [$user, $channel] = $this->channel(NotificationChannelEnum::Sms);
        $channel->fill(['destination' => '+15705550172', 'proven_at' => now()])->save();

        new UpdateNotificationChannel(
            user: $user,
            channel: $channel,
            isEnabled: true,
            destination: '+15705550172',
        )->execute();

        $channel->refresh();

        $this->assertTrue($channel->is_enabled);
        $this->assertSame('+15705550172', $channel->destination);
    }

    #[Test]
    public function it_refuses_to_switch_on_a_channel_with_nowhere_to_send(): void
    {
        [$user, $channel] = $this->channel(NotificationChannelEnum::Sms);

        $this->expectExceptionMessage('This channel needs somewhere to send to.');

        new UpdateNotificationChannel(
            user: $user,
            channel: $channel,
            isEnabled: true,
        )->execute();
    }

    #[Test]
    public function it_refuses_to_switch_on_a_text_message_nobody_has_proved(): void
    {
        [$user, $channel] = $this->channel(NotificationChannelEnum::Sms);

        $this->expectException(ChannelIsNotValid::class);

        new UpdateNotificationChannel(
            user: $user,
            channel: $channel,
            isEnabled: true,
            destination: '+15705550172',
        )->execute();
    }

    #[Test]
    public function a_proved_text_message_may_be_switched_on(): void
    {
        [$user, $channel] = $this->channel(NotificationChannelEnum::Sms);
        $channel->fill([
            'destination' => '+15705550172',
            'proven_at' => now(),
        ])->save();

        new UpdateNotificationChannel(
            user: $user,
            channel: $channel,
            isEnabled: true,
            destination: '+15705550172',
        )->execute();

        $this->assertTrue($channel->refresh()->is_enabled);
    }

    #[Test]
    public function it_refuses_to_switch_on_a_text_message_when_the_server_has_no_provider(): void
    {
        config(['vonage.api_key' => null]);

        [$user, $channel] = $this->channel(NotificationChannelEnum::Sms);
        $channel->fill(['destination' => '+15705550172', 'proven_at' => now()])->save();

        $this->expectExceptionMessage('This server has not been set up to send on this channel.');

        new UpdateNotificationChannel(
            user: $user,
            channel: $channel,
            isEnabled: true,
            destination: '+15705550172',
        )->execute();
    }

    #[Test]
    public function a_channel_may_always_be_switched_off_whatever_state_it_is_in(): void
    {
        [$user, $channel] = $this->channel(NotificationChannelEnum::Sms);

        new UpdateNotificationChannel(
            user: $user,
            channel: $channel,
            isEnabled: false,
        )->execute();

        $this->assertFalse($channel->refresh()->is_enabled);
    }

    #[Test]
    public function changing_the_destination_throws_away_the_proof(): void
    {
        [$user, $channel] = $this->channel(NotificationChannelEnum::Sms);
        $channel->fill([
            'destination' => '+15705550172',
            'proven_at' => now(),
            'proof_sent_count' => 2,
        ])->save();

        new UpdateNotificationChannel(
            user: $user,
            channel: $channel,
            isEnabled: false,
            destination: '+15705550199',
        )->execute();

        $channel->refresh();

        $this->assertNull($channel->proven_at);
        $this->assertSame(0, $channel->proof_sent_count);
        $this->assertSame('+15705550199', $channel->destination);
    }

    #[Test]
    public function switching_a_channel_back_on_forgets_why_it_was_switched_off(): void
    {
        [$user, $channel] = $this->channel(NotificationChannelEnum::Sms);
        $channel->fill([
            'destination' => '+15705550172',
            'proven_at' => now(),
            'consecutive_failures' => 5,
            'disabled_reason' => 'Nothing was getting through.',
        ])->save();

        new UpdateNotificationChannel(
            user: $user,
            channel: $channel,
            isEnabled: true,
            destination: '+15705550172',
        )->execute();

        $channel->refresh();

        $this->assertSame(0, $channel->consecutive_failures);
        $this->assertNull($channel->disabled_reason);
    }

    #[Test]
    public function a_channel_in_another_account_is_not_found(): void
    {
        [$user] = $this->channel(NotificationChannelEnum::Sms);
        [, $theirs] = $this->channel(NotificationChannelEnum::Sms);

        $this->expectException(ModelNotFoundException::class);

        new UpdateNotificationChannel(
            user: $user,
            channel: $theirs,
            isEnabled: false,
        )->execute();
    }

    #[Test]
    public function it_logs_switching_a_channel_on(): void
    {
        Queue::fake();
        [$user, $channel] = $this->channel(NotificationChannelEnum::Sms);
        $channel->fill(['destination' => '+15705550172', 'proven_at' => now()])->save();

        new UpdateNotificationChannel(
            user: $user,
            channel: $channel,
            isEnabled: true,
            destination: '+15705550172',
        )->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::NotificationChannelEnabled
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->parameters === ['name' => 'Text message']
                && $job->isAbout($channel),
        );
    }

    #[Test]
    public function it_logs_switching_a_channel_off(): void
    {
        Queue::fake();
        [$user, $channel] = $this->channel(NotificationChannelEnum::Email);
        $channel->update(['is_enabled' => true]);

        new UpdateNotificationChannel(user: $user, channel: $channel, isEnabled: false)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::NotificationChannelDisabled,
        );
    }

    #[Test]
    public function it_logs_a_settings_change_that_leaves_the_switch_alone(): void
    {
        Queue::fake();
        [$user, $channel] = $this->channel(NotificationChannelEnum::Sms);

        new UpdateNotificationChannel(
            user: $user,
            channel: $channel,
            isEnabled: false,
            destination: '+15705550172',
        )->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::NotificationChannelUpdate,
        );
    }

    /** @return array{0: User, 1: NotificationChannel} */
    private function channel(NotificationChannelEnum $channel): array
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $row = NotificationChannel::query()
            ->where('account_id', $account->id)
            ->where('channel', $channel->value)
            ->firstOrFail();

        return [$user, $row];
    }
}
