<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RecordChannelFailure;
use App\Enums\LogSourceEnum;
use App\Enums\NotificationChannelEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\ChannelDisabled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordChannelFailureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_one_refusal_against_the_channel(): void
    {
        NotificationFacade::fake();
        $channel = $this->channel();

        new RecordChannelFailure(channel: $channel, reason: 'The room does not exist.')->execute();

        $this->assertDatabaseHas('notification_channels', [
            'id' => $channel->id,
            'consecutive_failures' => 1,
            'is_enabled' => true,
        ]);
        NotificationFacade::assertNothingSent();
    }

    #[Test]
    public function it_switches_the_channel_off_once_the_refusals_reach_the_threshold(): void
    {
        NotificationFacade::fake();
        $channel = $this->channel();
        $channel->update(['consecutive_failures' => (int) config('monica.notification_channel_failure_threshold') - 1]);

        new RecordChannelFailure(channel: $channel, reason: 'The token has been revoked.')->execute();

        $this->assertDatabaseHas('notification_channels', [
            'id' => $channel->id,
            'is_enabled' => false,
            'disabled_reason' => 'The token has been revoked.',
        ]);
    }

    #[Test]
    public function it_says_so_on_a_channel_that_still_works(): void
    {
        NotificationFacade::fake();
        $channel = $this->channel();
        $channel->update(['consecutive_failures' => (int) config('monica.notification_channel_failure_threshold') - 1]);

        new RecordChannelFailure(channel: $channel, reason: 'The token has been revoked.')->execute();

        NotificationFacade::assertSentTo(
            $channel->account->user,
            ChannelDisabled::class,
            fn (ChannelDisabled $sent): bool => $sent->channelName === 'Telegram'
                && $sent->reason === 'The token has been revoked.'
                && $sent->via($channel->account->user) === ['mail'],
        );
    }

    #[Test]
    public function it_says_so_once_rather_than_on_every_refusal_after(): void
    {
        NotificationFacade::fake();
        $channel = $this->channel();
        $channel->update([
            'consecutive_failures' => (int) config('monica.notification_channel_failure_threshold'),
            'is_enabled' => false,
            'disabled_reason' => 'The token has been revoked.',
        ]);

        new RecordChannelFailure(channel: $channel, reason: 'Still nothing.')->execute();

        NotificationFacade::assertNothingSent();
    }

    #[Test]
    public function a_reason_longer_than_the_column_is_cut_rather_than_refused(): void
    {
        NotificationFacade::fake();
        $channel = $this->channel();
        $channel->update(['consecutive_failures' => (int) config('monica.notification_channel_failure_threshold') - 1]);

        new RecordChannelFailure(channel: $channel, reason: str_repeat('a', 400))->execute();

        $this->assertSame(255, mb_strlen((string) $channel->refresh()->disabled_reason));
    }

    #[Test]
    public function any_success_forgets_what_was_counted(): void
    {
        $channel = $this->channel();
        $channel->update(['consecutive_failures' => 3]);

        $channel->clearFailures();

        $this->assertSame(0, $channel->refresh()->consecutive_failures);
    }

    #[Test]
    public function it_logs_that_the_product_gave_up_on_the_channel(): void
    {
        NotificationFacade::fake();
        Queue::fake();
        $channel = $this->channel();
        $channel->update(['consecutive_failures' => (int) config('monica.notification_channel_failure_threshold') - 1]);

        new RecordChannelFailure(channel: $channel, reason: 'The token has been revoked.')->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::NotificationChannelGaveUp
                && $job->user === null
                && $job->source === LogSourceEnum::System
                && $job->parameters === ['name' => 'Telegram', 'reason' => 'The token has been revoked.'],
        );
    }

    #[Test]
    public function it_logs_nothing_for_a_refusal_that_did_not_reach_the_threshold(): void
    {
        NotificationFacade::fake();
        Queue::fake();

        new RecordChannelFailure(channel: $this->channel(), reason: 'A hiccup.')->execute();

        Queue::assertNotPushed(LogUserAction::class);
    }

    private function channel(): NotificationChannel
    {
        $account = Account::factory()->create();
        User::factory()->create(['account_id' => $account->id]);

        $channel = NotificationChannel::query()
            ->where('account_id', $account->id)
            ->where('channel', NotificationChannelEnum::Telegram->value)
            ->firstOrFail();

        $channel->fill([
            'is_enabled' => true,
            'destination' => '918273645',
            'proven_at' => now(),
        ])->save();

        return $channel;
    }
}
