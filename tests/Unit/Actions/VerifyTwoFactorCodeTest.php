<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\VerifyTwoFactorCode;
use App\Models\SecurityKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class VerifyTwoFactorCodeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_refuses_the_code_of_an_authenticator_app_nobody_finished_pairing(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => null,
        ]);
        SecurityKey::factory()->for($user)->create();

        $verified = new VerifyTwoFactorCode(
            user: $user,
            code: $google2fa->getCurrentOtp($secret),
        )->execute();

        $this->assertFalse($verified);
    }

    #[Test]
    public function it_accepts_the_code_the_authenticator_app_is_showing(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = User::factory()->twoFactor($secret)->create();

        $verified = new VerifyTwoFactorCode(
            user: $user,
            code: $google2fa->getCurrentOtp($secret),
        )->execute();

        $this->assertTrue($verified);
    }

    #[Test]
    public function it_refuses_a_code_that_is_not_right(): void
    {
        $user = User::factory()->twoFactor()->create();

        $verified = new VerifyTwoFactorCode(
            user: $user,
            code: '000000',
        )->execute();

        $this->assertFalse($verified);
    }

    #[Test]
    public function it_accepts_a_recovery_code_and_spends_it(): void
    {
        $user = User::factory()->twoFactor()->create();

        $verified = new VerifyTwoFactorCode(
            user: $user,
            code: 'scranton-1',
        )->execute();

        $this->assertTrue($verified);
        $this->assertEquals(['scranton-2'], $user->refresh()->two_factor_recovery_codes);
    }

    #[Test]
    public function it_refuses_a_recovery_code_that_was_already_spent(): void
    {
        $user = User::factory()->twoFactor()->create();

        new VerifyTwoFactorCode(user: $user, code: 'scranton-1')->execute();

        $verified = new VerifyTwoFactorCode(
            user: $user->refresh(),
            code: 'scranton-1',
        )->execute();

        $this->assertFalse($verified);
    }

    #[Test]
    public function it_refuses_even_the_right_code_after_five_wrong_ones(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = User::factory()->twoFactor($secret)->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            new VerifyTwoFactorCode(user: $user, code: '000000')->execute();
        }

        $this->expectException(ValidationException::class);

        new VerifyTwoFactorCode(
            user: $user,
            code: $google2fa->getCurrentOtp($secret),
        )->execute();
    }

    #[Test]
    public function it_forgets_the_wrong_codes_once_a_right_one_is_typed(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = User::factory()->twoFactor($secret)->create();

        for ($attempt = 0; $attempt < 4; $attempt++) {
            new VerifyTwoFactorCode(user: $user, code: '000000')->execute();
        }

        new VerifyTwoFactorCode(user: $user, code: 'scranton-1')->execute();

        for ($attempt = 0; $attempt < 4; $attempt++) {
            new VerifyTwoFactorCode(user: $user, code: '000000')->execute();
        }

        $verified = new VerifyTwoFactorCode(
            user: $user->refresh(),
            code: $google2fa->getCurrentOtp($secret),
        )->execute();

        $this->assertTrue($verified);
    }

    #[Test]
    public function it_refuses_anybody_who_never_enrolled(): void
    {
        $user = User::factory()->create();

        $verified = new VerifyTwoFactorCode(
            user: $user,
            code: '000000',
        )->execute();

        $this->assertFalse($verified);
    }
}
