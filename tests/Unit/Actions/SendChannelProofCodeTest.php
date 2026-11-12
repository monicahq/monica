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
use Illuminate\Auth\Access\AuthorizationException;
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

        try {
            new SendChannelProofCode(user: $user, channel: $channel)->execute();
            $this->fail('A code was sent past the limit.');
        } catch (AuthorizationException $e) {
        }

        NotificationFacade::assertNothingSent();
    }

    #[Test]
    public function another_code_sent_after_the_wait_replaces_the_first_with_a_full_set_of_tries(): void
    {
        NotificationFacade::fake();
        $this->freezeTime();
        [$user, $channel] = $this->readyTextMessage();
        $channel->update([
            'proof_code_hash' => Hash::make('111111'),
            'proof_expires_at' => now()->subMinute(),
            'proof_attempts' => 5,
            'proof_sent_count' => 1,
            'proof_last_sent_at' => now()->subSeconds((int) config('monica.notification_proof_resend_seconds')),
        ]);

        new SendChannelProofCode(user: $user, channel: $channel)->execute();

        $channel->refresh();

        $this->assertFalse(Hash::check('111111', (string) $channel->proof_code_hash));
        $this->assertSame(0, $channel->proof_attempts);
        $this->assertSame(2, $channel->proof_sent_count);
        $this->assertEqualsWithDelta(
            now()->addMinutes((int) config('monica.notification_proof_code_minutes'))->timestamp,
            $channel->proof_expires_at->timestamp,
            1,
        );
    }

    #[Test]
    public function no_code_is_sent_inside_the_wait(): void
    {
        NotificationFacade::fake();
        $this->freezeTime();
        [$user, $channel] = $this->readyTextMessage();
        $channel->update(['proof_sent_count' => 1, 'proof_last_sent_at' => now()->subSeconds(20)]);

        try {
            new SendChannelProofCode(user: $user, channel: $channel)->execute();
            $this->fail('A code was sent inside the wait.');
        } catch (AuthorizationException $e) {
        }

        NotificationFacade::assertNothingSent();
        $this->assertSame(1, $channel->refresh()->proof_sent_count);
    }

    #[Test]
    public function a_request_that_loses_the_race_inside_the_wait_sends_nothing(): void
    {
        NotificationFacade::fake();
        $this->freezeTime();
        [$user, $channel] = $this->readyTextMessage();

        NotificationChannel::query()->whereKey($channel->id)->update(['proof_last_sent_at' => now()]);

        try {
            new SendChannelProofCode(user: $user, channel: $channel)->execute();
            $this->fail('The request that lost the race sent a code.');
        } catch (AuthorizationException) {
            NotificationFacade::assertNothingSent();
        }
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
