<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\StartChannelConnection;
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

class StartChannelConnectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_mints_a_token_that_lapses(): void
    {
        [$user, $channel] = $this->channel(NotificationChannelEnum::Telegram);

        $connection = new StartChannelConnection(user: $user, channel: $channel);
        $connection->execute();

        $channel->refresh();

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{64}$/', $connection->token());
        $this->assertSame(hash('sha256', $connection->token()), $channel->verification_token_hash);
        $this->assertTrue($channel->isAwaitingConnection());
        $this->assertEqualsWithDelta(
            now()->addMinutes((int) config('monica.notification_proof_code_minutes'))->timestamp,
            $channel->proof_expires_at->timestamp,
            1,
        );
    }

    #[Test]
    public function starting_again_throws_away_the_conversation_that_was_connected(): void
    {
        [$user, $channel] = $this->channel(NotificationChannelEnum::Telegram);
        $channel->fill(['destination' => '918273645', 'proven_at' => now(), 'is_enabled' => true])->save();

        new StartChannelConnection(user: $user, channel: $channel)->execute();

        $channel->refresh();

        $this->assertNull($channel->destination);
        $this->assertNull($channel->proven_at);
        $this->assertFalse($channel->is_enabled);
    }

    #[Test]
    public function it_refuses_a_channel_that_is_told_where_to_send(): void
    {
        [$user, $channel] = $this->channel(NotificationChannelEnum::Sms);

        $this->expectExceptionMessage('This channel is told where to send rather than telling us.');

        new StartChannelConnection(user: $user, channel: $channel)->execute();
    }

    #[Test]
    public function it_refuses_when_the_server_has_no_bot(): void
    {
        config(['services.telegram.token' => null]);

        [$user, $channel] = $this->channel(NotificationChannelEnum::Telegram);

        $this->expectException(ChannelIsNotValid::class);

        new StartChannelConnection(user: $user, channel: $channel)->execute();
    }

    #[Test]
    public function a_channel_in_another_account_is_not_found(): void
    {
        [$user] = $this->channel(NotificationChannelEnum::Telegram);
        [, $theirs] = $this->channel(NotificationChannelEnum::Telegram);

        $this->expectException(ModelNotFoundException::class);

        new StartChannelConnection(user: $user, channel: $theirs)->execute();
    }

    #[Test]
    public function it_logs_that_connecting_was_started(): void
    {
        Queue::fake();
        [$user, $channel] = $this->channel(NotificationChannelEnum::Telegram);

        new StartChannelConnection(user: $user, channel: $channel)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::NotificationChannelConnectionStarted
                && $job->parameters === ['name' => 'Telegram'],
        );
    }

    #[Test]
    public function it_writes_down_only_the_hash_so_the_link_cannot_be_rebuilt_from_the_table(): void
    {
        [$user, $channel] = $this->channel(NotificationChannelEnum::Telegram);

        $connection = new StartChannelConnection(user: $user, channel: $channel);
        $connection->execute();

        $stored = (string) $this->getConnection()
            ->table('notification_channels')
            ->where('id', $channel->id)
            ->value('verification_token_hash');

        $this->assertStringNotContainsString($connection->token(), $stored);
        $this->assertSame(64, mb_strlen($stored));
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
