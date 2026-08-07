<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ConfirmTwoFactorAuthentication;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class ConfirmTwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_turns_two_factor_authentication_on_and_hands_out_recovery_codes(): void
    {
        Queue::fake();

        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        $confirmed = new ConfirmTwoFactorAuthentication(
            user: $user,
            code: $google2fa->getCurrentOtp($secret),
        )->execute();

        $this->assertTrue($confirmed);

        $user->refresh();

        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertTrue($user->usesTwoFactorAuthentication());
        $this->assertCount(8, $user->two_factor_recovery_codes);

        foreach ($user->two_factor_recovery_codes as $code) {
            $this->assertEquals(10, mb_strlen($code));
        }

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TwoFactorEnabled
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_refuses_a_code_that_is_not_right_and_leaves_the_account_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'two_factor_secret' => new Google2FA()->generateSecretKey(),
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        $confirmed = new ConfirmTwoFactorAuthentication(
            user: $user,
            code: '000000',
        )->execute();

        $this->assertFalse($confirmed);

        $user->refresh();

        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertNull($user->two_factor_recovery_codes);

        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_refuses_when_no_secret_was_ever_written_down(): void
    {
        Queue::fake();

        $user = User::factory()->create(['two_factor_secret' => null]);

        $confirmed = new ConfirmTwoFactorAuthentication(
            user: $user,
            code: '123456',
        )->execute();

        $this->assertFalse($confirmed);
        $this->assertNull($user->refresh()->two_factor_confirmed_at);
    }
}
