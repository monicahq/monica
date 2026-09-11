<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CompleteChannelConnection;
use App\Enums\LogSourceEnum;
use App\Enums\NotificationChannelEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompleteChannelConnectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_the_destination_the_service_handed_back(): void
    {
        $channel = $this->awaiting('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');

        $returned = new CompleteChannelConnection(
            verificationToken: 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            destination: '918273645',
        )->execute();

        $this->assertNotNull($returned);
        $channel->refresh();

        $this->assertSame('918273645', $channel->destination);
        $this->assertNotNull($channel->proven_at);
        $this->assertNull($channel->verification_token_hash);
        $this->assertNull($channel->proof_expires_at);
        $this->assertTrue($channel->is_enabled);
    }

    #[Test]
    public function it_forgets_why_the_channel_had_been_switched_off(): void
    {
        $channel = $this->awaiting('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        $channel->update(['consecutive_failures' => 5, 'disabled_reason' => 'The bot was blocked.']);

        new CompleteChannelConnection(
            verificationToken: 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            destination: '918273645',
        )->execute();

        $channel->refresh();

        $this->assertSame(0, $channel->consecutive_failures);
        $this->assertNull($channel->disabled_reason);
    }

    #[Test]
    public function it_leaves_the_channel_off_when_the_server_cannot_send_on_it(): void
    {
        $channel = $this->awaiting('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        config(['services.telegram.token' => null]);

        new CompleteChannelConnection(
            verificationToken: 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            destination: '918273645',
        )->execute();

        $this->assertFalse($channel->refresh()->is_enabled);
    }

    #[Test]
    public function a_token_nobody_is_waiting_on_changes_nothing(): void
    {
        $channel = $this->awaiting('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');

        $this->assertNull(new CompleteChannelConnection(
            verificationToken: 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc',
            destination: '918273645',
        )->execute());

        $this->assertNull($channel->refresh()->destination);
    }

    #[Test]
    public function an_expired_token_changes_nothing(): void
    {
        $channel = $this->awaiting('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        $channel->update(['proof_expires_at' => now()->subMinute()]);

        $this->assertNull(new CompleteChannelConnection(
            verificationToken: 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            destination: '918273645',
        )->execute());

        $this->assertNull($channel->refresh()->destination);
    }

    #[Test]
    public function it_logs_the_connection_against_the_account_the_token_belonged_to(): void
    {
        Queue::fake();
        $channel = $this->awaiting('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');

        new CompleteChannelConnection(
            verificationToken: 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            destination: '918273645',
        )->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::NotificationChannelConnected
                && $job->account->id === $channel->account_id
                && $job->user->id === $channel->account->user->id
                && $job->source === LogSourceEnum::Automation
                && $job->sourceDetail === 'telegram'
                && $job->isAbout($channel),
        );
    }

    #[Test]
    public function it_logs_nothing_for_a_token_nobody_is_waiting_on(): void
    {
        Queue::fake();
        $this->awaiting('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');

        new CompleteChannelConnection(
            verificationToken: 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc',
            destination: '918273645',
        )->execute();

        Queue::assertNotPushed(LogUserAction::class);
    }

    private function awaiting(string $token): NotificationChannel
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
