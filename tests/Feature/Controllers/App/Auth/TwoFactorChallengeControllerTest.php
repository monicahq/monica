<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorChallengeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_challenge_to_somebody_whose_password_was_accepted(): void
    {
        $user = User::factory()->twoFactor()->create();

        $response = $this->withSession(['twoFactor.user.id' => $user->id])
            ->get(route('auth.twoFactor.new'));

        $response->assertStatus(200);
        $response->assertSee('One more step', escape: false);
    }

    #[Test]
    public function it_sends_somebody_back_to_the_sign_in_screen_when_no_password_was_accepted(): void
    {
        $response = $this->get(route('auth.twoFactor.new'));

        $response->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_signs_somebody_in_when_the_code_is_right(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = User::factory()->twoFactor($secret)->create();

        $response = $this->withSession(['twoFactor.user.id' => $user->id])
            ->post(route('auth.twoFactor.create'), [
                'code' => $google2fa->getCurrentOtp($secret),
            ]);

        $response->assertRedirect(route('home.index'));
        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('twoFactor.user.id'));
    }

    #[Test]
    public function it_signs_somebody_in_with_a_recovery_code_and_spends_it(): void
    {
        $user = User::factory()->twoFactor()->create();

        $response = $this->withSession(['twoFactor.user.id' => $user->id])
            ->post(route('auth.twoFactor.create'), ['code' => 'scranton-1']);

        $response->assertRedirect(route('home.index'));
        $this->assertAuthenticatedAs($user);
        $this->assertEquals(['scranton-2'], $user->refresh()->two_factor_recovery_codes);
    }

    #[Test]
    public function it_refuses_a_code_that_is_not_right(): void
    {
        $user = User::factory()->twoFactor()->create();

        $response = $this->withSession(['twoFactor.user.id' => $user->id])
            ->post(route('auth.twoFactor.create'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }
}
