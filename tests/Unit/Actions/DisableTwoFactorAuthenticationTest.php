<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DisableTwoFactorAuthentication;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\SecurityKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DisableTwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_forgets_the_secret_and_the_recovery_codes(): void
    {
        Queue::fake();

        $account = Account::factory()->create();
        $user = User::factory()->twoFactor()->create(['account_id' => $account->id]);

        $result = new DisableTwoFactorAuthentication(user: $user)->execute();

        $this->assertInstanceOf(User::class, $result);

        $user->refresh();

        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertFalse($user->usesTwoFactorAuthentication());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TwoFactorDisabled
                && $job->account->id === $account->id
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_keeps_two_factor_authentication_on_while_a_security_key_remains(): void
    {
        Queue::fake();

        $user = User::factory()->twoFactor()->create();
        SecurityKey::factory()->for($user)->create();

        new DisableTwoFactorAuthentication(user: $user)->execute();

        $user->refresh();

        $this->assertFalse($user->usesAuthenticatorApp());
        $this->assertTrue($user->usesTwoFactorAuthentication());
        $this->assertEquals(['scranton-1', 'scranton-2'], $user->two_factor_recovery_codes);
        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::AuthenticatorAppRemoved);
    }
}
