<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\NotificationChannelEnum;
use App\Models\Account;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationChannelPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_send_proof_code_for_a_number_never_sent_a_code(): void
    {
        [$user, $channel] = $this->numberOf(NotificationChannelEnum::Sms);

        $this->assertTrue(Gate::forUser($user)->allows('sendProofCode', $channel));
    }

    #[Test]
    public function it_allows_send_proof_code_once_the_wait_has_passed(): void
    {
        [$user, $channel] = $this->numberOf(NotificationChannelEnum::Sms);
        $channel->update(['proof_sent_count' => 1, 'proof_last_sent_at' => now()->subSeconds(61)]);

        $this->assertTrue(Gate::forUser($user)->allows('sendProofCode', $channel));
    }

    #[Test]
    public function it_refuses_send_proof_code_inside_the_wait_and_says_how_long_is_left(): void
    {
        $this->freezeTime();
        [$user, $channel] = $this->numberOf(NotificationChannelEnum::Sms);
        $channel->update(['proof_sent_count' => 1, 'proof_last_sent_at' => now()->subSeconds(30)]);

        $answer = Gate::forUser($user)->inspect('sendProofCode', $channel);

        $this->assertTrue($answer->denied());
    }

    #[Test]
    public function it_refuses_send_proof_code_when_nobody_is_signed_in(): void
    {
        [, $channel] = $this->numberOf(NotificationChannelEnum::Sms);

        $this->assertFalse(Gate::forUser(null)->allows('sendProofCode', $channel));
    }

    /** @return array{0: User, 1: NotificationChannel} */
    private function numberOf(NotificationChannelEnum $channel): array
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $row = NotificationChannel::query()
            ->where('account_id', $account->id)
            ->where('channel', $channel->value)
            ->firstOrFail();

        $row->update(['destination' => '+15705550172']);

        return [$user, $row];
    }
}
