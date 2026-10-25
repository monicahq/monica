<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CompleteChannelConnection;
use App\Actions\ConfirmChannelProofCode;
use App\Actions\RemoveChannelSetup;
use App\Enums\NotificationChannelEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ChannelIsNotValid;
use App\Exceptions\ProofCodeIsNotValid;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RemoveChannelSetupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_erases_a_proven_number_and_switches_the_channel_off(): void
    {
        Queue::fake();
        [$user, $channel] = $this->channelOf(NotificationChannelEnum::Sms);
        $channel->fill([
            'destination' => '+15705550172',
            'is_enabled' => true,
            'proven_at' => now(),
            'proof_code_hash' => Hash::make('123456'),
            'proof_expires_at' => now()->addMinutes(10),
            'proof_attempts' => 2,
            'proof_sent_count' => 2,
            'proof_last_sent_at' => now(),
        ])->save();

        new RemoveChannelSetup(user: $user, channel: $channel)->execute();

        $this->assertDatabaseHas('notification_channels', [
            'id' => $channel->id,
            'destination' => null,
            'is_enabled' => false,
            'proven_at' => null,
            'proof_code_hash' => null,
            'proof_expires_at' => null,
            'proof_attempts' => 0,
            'proof_sent_count' => 0,
            'proof_last_sent_at' => null,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::NotificationChannelSetupRemoved
                && $job->parameters === ['name' => 'Text message'],
        );
    }

    #[Test]
    public function it_clears_why_the_product_switched_the_channel_off(): void
    {
        Queue::fake();
        [$user, $channel] = $this->channelOf(NotificationChannelEnum::Sms);
        $channel->fill([
            'destination' => '+15705550172',
            'consecutive_failures' => 5,
            'disabled_reason' => 'It stopped accepting anything we sent.',
        ])->save();

        new RemoveChannelSetup(user: $user, channel: $channel)->execute();

        $this->assertDatabaseHas('notification_channels', [
            'id' => $channel->id,
            'consecutive_failures' => 0,
            'disabled_reason' => null,
        ]);
    }

    #[Test]
    public function the_code_sent_before_the_removal_is_no_longer_accepted(): void
    {
        Queue::fake();
        [$user, $channel] = $this->channelOf(NotificationChannelEnum::Sms);
        $channel->fill([
            'destination' => '+15705550172',
            'proof_code_hash' => Hash::make('123456'),
            'proof_expires_at' => now()->addMinutes(10),
        ])->save();

        new RemoveChannelSetup(user: $user, channel: $channel)->execute();

        $this->expectException(ProofCodeIsNotValid::class);

        new ConfirmChannelProofCode(user: $user, channel: $channel->refresh(), code: '123456')->execute();
    }

    #[Test]
    public function a_telegram_connection_started_before_the_removal_no_longer_connects(): void
    {
        Queue::fake();
        [$user, $channel] = $this->channelOf(NotificationChannelEnum::Telegram);
        $channel->fill([
            'verification_token_hash' => hash('sha256', 'scranton-branch'),
            'proof_expires_at' => now()->addMinutes(10),
        ])->save();

        new RemoveChannelSetup(user: $user, channel: $channel)->execute();

        $connected = new CompleteChannelConnection(verificationToken: 'scranton-branch', destination: '424242')->execute();

        $this->assertNull($connected);
        $this->assertDatabaseHas('notification_channels', [
            'id' => $channel->id,
            'destination' => null,
            'verification_token_hash' => null,
        ]);
    }

    #[Test]
    public function a_channel_holding_nothing_is_left_alone_and_nothing_is_journalled(): void
    {
        Queue::fake();
        [$user, $channel] = $this->channelOf(NotificationChannelEnum::Sms);
        $updatedAt = $channel->updated_at;

        new RemoveChannelSetup(user: $user, channel: $channel)->execute();

        $this->assertEquals($updatedAt, $channel->refresh()->updated_at);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function email_has_nothing_to_remove(): void
    {
        [$user, $channel] = $this->channelOf(NotificationChannelEnum::Email);

        $this->expectExceptionObject(new ChannelIsNotValid('This channel has nothing to remove.'));

        new RemoveChannelSetup(user: $user, channel: $channel)->execute();
    }

    #[Test]
    public function a_channel_in_another_account_is_not_found(): void
    {
        [$user] = $this->channelOf(NotificationChannelEnum::Sms);
        [, $theirs] = $this->channelOf(NotificationChannelEnum::Sms);

        $this->expectException(ModelNotFoundException::class);

        new RemoveChannelSetup(user: $user, channel: $theirs)->execute();
    }

    /** @return array{0: User, 1: NotificationChannel} */
    private function channelOf(NotificationChannelEnum $channel): array
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
