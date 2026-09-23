<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SendChannelProofCode;
use App\Enums\NotificationChannelEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ChannelIsNotValid;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\ChannelProofCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendChannelProofCodeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_a_code_to_the_destination_and_keeps_only_its_hash(): void
    {
        NotificationFacade::fake();
        Queue::fake();
        [$user, $channel] = $this->readyTextMessage();

        new SendChannelProofCode(user: $user, channel: $channel)->execute();

        $channel->refresh();

        $this->assertNotNull($channel->proof_code_hash);
        $this->assertSame(1, $channel->proof_sent_count);
        $this->assertNotNull($channel->proof_last_sent_at);

        NotificationFacade::assertSentTo(
            $user,
            ChannelProofCode::class,
            function (ChannelProofCode $sent) use ($channel): bool {
                return preg_match('/^\d{6}$/', $sent->code) === 1
                    && Hash::check($sent->code, (string) $channel->proof_code_hash)
                    && $sent->via($channel->account->user) === ['vonage'];
            },
        );

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::NotificationChannelProofRequested
                && $job->parameters === ['name' => 'Text message'],
        );
    }

    #[Test]
    public function the_code_stops_counting_after_the_time_the_instance_allows(): void
    {
        NotificationFacade::fake();
        $this->freezeTime();
        [$user, $channel] = $this->readyTextMessage();

        new SendChannelProofCode(user: $user, channel: $channel)->execute();

        $this->assertEqualsWithDelta(
            now()->addMinutes((int) config('monica.notification_proof_code_minutes'))->timestamp,
            $channel->refresh()->proof_expires_at->timestamp,
            1,
        );
    }

    #[Test]
    public function it_forgets_the_wrong_guesses_counted_against_the_code_before(): void
    {
        NotificationFacade::fake();
        [$user, $channel] = $this->readyTextMessage();
        $channel->update(['proof_attempts' => 3]);

        new SendChannelProofCode(user: $user, channel: $channel)->execute();

        $this->assertSame(0, $channel->refresh()->proof_attempts);
    }

    #[Test]
    public function it_stops_sending_codes_to_the_same_destination_past_the_limit(): void
    {
        NotificationFacade::fake();
        [$user, $channel] = $this->readyTextMessage();
        $channel->update(['proof_sent_count' => (int) config('monica.notification_proof_maximum_sends')]);

        $this->expectException(ChannelIsNotValid::class);

        new SendChannelProofCode(user: $user, channel: $channel)->execute();
    }

    #[Test]
    public function it_refuses_a_channel_that_says_for_itself_when_a_destination_is_wrong(): void
    {
        NotificationFacade::fake();
        [$user, $channel] = $this->readyTextMessage(NotificationChannelEnum::Telegram);

        $this->expectExceptionMessage('This channel says for itself whether a destination is wrong, so it has nothing to prove.');

        new SendChannelProofCode(user: $user, channel: $channel)->execute();
    }

    #[Test]
    public function it_sends_no_code_when_the_server_has_no_provider_to_send_it_with(): void
    {
        NotificationFacade::fake();
        config(['vonage.api_key' => null]);

        [$user, $channel] = $this->readyTextMessage();

        $this->expectExceptionMessage('This server has not been set up to send on this channel, so no code can be sent.');

        new SendChannelProofCode(user: $user, channel: $channel)->execute();
    }

    #[Test]
    public function it_refuses_a_channel_that_is_not_finished(): void
    {
        NotificationFacade::fake();
        [$user, $channel] = $this->readyTextMessage();
        $channel->update(['destination' => null]);

        $this->expectException(ChannelIsNotValid::class);

        new SendChannelProofCode(user: $user, channel: $channel)->execute();
    }

    #[Test]
    public function a_channel_in_another_account_is_not_found(): void
    {
        NotificationFacade::fake();
        [$user] = $this->readyTextMessage();
        [, $theirs] = $this->readyTextMessage();

        $this->expectException(ModelNotFoundException::class);

        new SendChannelProofCode(user: $user, channel: $theirs)->execute();
    }

    /** @return array{0: User, 1: NotificationChannel} */
    private function readyTextMessage(NotificationChannelEnum $channel = NotificationChannelEnum::Sms): array
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $row = NotificationChannel::query()
            ->where('account_id', $account->id)
            ->where('channel', $channel->value)
            ->firstOrFail();

        $row->fill([
            'destination' => '+15705550172',
        ])->save();

        return [$user, $row];
    }
}
