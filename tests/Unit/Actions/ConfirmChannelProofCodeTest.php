<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ConfirmChannelProofCode;
use App\Enums\NotificationChannelEnum;
use App\Enums\UserActionEnum;
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

class ConfirmChannelProofCodeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_right_code_proves_the_destination_is_the_accounts_own(): void
    {
        Queue::fake();
        [$user, $channel] = $this->awaitingCode();

        new ConfirmChannelProofCode(user: $user, channel: $channel, code: '123456')->execute();

        $channel->refresh();

        $this->assertNotNull($channel->proven_at);
        $this->assertNull($channel->proof_code_hash);
        $this->assertNull($channel->proof_expires_at);
        $this->assertSame(0, $channel->proof_attempts);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::NotificationChannelProven,
        );
    }

    #[Test]
    public function a_wrong_code_is_counted_and_refused(): void
    {
        [$user, $channel] = $this->awaitingCode();

        try {
            new ConfirmChannelProofCode(user: $user, channel: $channel, code: '000000')->execute();
            $this->fail('A wrong code was accepted.');
        } catch (ProofCodeIsNotValid $e) {
            $this->assertSame('That is not the code we sent.', $e->getMessage());
        }

        $channel->refresh();

        $this->assertNull($channel->proven_at);
        $this->assertSame(1, $channel->proof_attempts);
    }

    #[Test]
    public function it_says_when_the_code_has_expired_rather_than_that_it_is_wrong(): void
    {
        [$user, $channel] = $this->awaitingCode();
        $channel->update(['proof_expires_at' => now()->subMinute()]);

        $this->expectExceptionMessage('This code has expired. Ask for another one.');

        new ConfirmChannelProofCode(user: $user, channel: $channel, code: '123456')->execute();
    }

    #[Test]
    public function it_says_when_the_code_has_been_tried_too_many_times(): void
    {
        [$user, $channel] = $this->awaitingCode();
        $channel->update(['proof_attempts' => (int) config('monica.notification_proof_maximum_attempts')]);

        $this->expectExceptionMessage('This code has been tried too many times. Ask for another one.');

        new ConfirmChannelProofCode(user: $user, channel: $channel, code: '123456')->execute();
    }

    #[Test]
    public function a_code_that_ran_out_of_tries_stays_spent_even_when_the_right_one_is_typed(): void
    {
        [$user, $channel] = $this->awaitingCode();
        $channel->update(['proof_attempts' => (int) config('monica.notification_proof_maximum_attempts')]);

        try {
            new ConfirmChannelProofCode(user: $user, channel: $channel, code: '123456')->execute();
        } catch (ProofCodeIsNotValid) {
            // the assertion below is the point
        }

        $this->assertNull($channel->refresh()->proven_at);
    }

    #[Test]
    public function it_says_when_no_code_was_ever_sent(): void
    {
        [$user, $channel] = $this->awaitingCode();
        $channel->update(['proof_code_hash' => null]);

        $this->expectExceptionMessage('No code has been sent to this destination.');

        new ConfirmChannelProofCode(user: $user, channel: $channel, code: '123456')->execute();
    }

    #[Test]
    public function a_channel_in_another_account_is_not_found(): void
    {
        [$user] = $this->awaitingCode();
        [, $theirs] = $this->awaitingCode();

        $this->expectException(ModelNotFoundException::class);

        new ConfirmChannelProofCode(user: $user, channel: $theirs, code: '123456')->execute();
    }

    #[Test]
    public function it_logs_nothing_when_the_code_was_wrong(): void
    {
        Queue::fake();
        [$user, $channel] = $this->awaitingCode();

        try {
            new ConfirmChannelProofCode(user: $user, channel: $channel, code: '000000')->execute();
        } catch (ProofCodeIsNotValid) {
            // the assertion below is the point
        }

        Queue::assertNotPushed(LogUserAction::class);
    }

    /** @return array{0: User, 1: NotificationChannel} */
    private function awaitingCode(): array
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $row = NotificationChannel::query()
            ->where('account_id', $account->id)
            ->where('channel', NotificationChannelEnum::Sms->value)
            ->firstOrFail();

        $row->fill([
            'destination' => '+15705550172',
            'proof_code_hash' => Hash::make('123456'),
            'proof_expires_at' => now()->addMinutes(10),
            'proof_sent_count' => 1,
        ])->save();

        return [$user, $row];
    }
}
