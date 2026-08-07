<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\EnableTwoFactorAuthentication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnableTwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_down_a_secret_and_draws_the_square_for_it(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $enrolment = new EnableTwoFactorAuthentication(user: $user)->execute();

        $this->assertNotEmpty($enrolment['secret']);
        $this->assertEquals($enrolment['secret'], $user->refresh()->two_factor_secret);

        $this->assertStringStartsWith('<svg', $enrolment['qrCode']);
        $this->assertStringNotContainsString('<?xml', $enrolment['qrCode']);
    }

    #[Test]
    public function it_leaves_the_account_unprotected_until_the_code_is_confirmed(): void
    {
        $user = User::factory()->create();

        new EnableTwoFactorAuthentication(user: $user)->execute();

        $user->refresh();

        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertFalse($user->usesTwoFactorAuthentication());
    }

    #[Test]
    public function it_mints_a_new_secret_every_time_and_forgets_what_came_before(): void
    {
        $user = User::factory()->twoFactor()->create();

        $first = new EnableTwoFactorAuthentication(user: $user)->execute();
        $second = new EnableTwoFactorAuthentication(user: $user)->execute();

        $this->assertNotEquals($first['secret'], $second['secret']);
        $this->assertEquals($second['secret'], $user->refresh()->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
    }
}
