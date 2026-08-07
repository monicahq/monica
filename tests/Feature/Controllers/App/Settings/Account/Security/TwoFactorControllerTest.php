<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_screen_that_pairs_an_authenticator_app(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->actingAs($user)->get(route('settings.twoFactor.new'));

        $response->assertStatus(200);
        $response->assertSee('Pair your authenticator app', escape: false);
        $response->assertSee('michael.scott@dundermifflin.com', escape: false);
        $response->assertSee('<svg', escape: false);

        $this->assertNotNull($user->refresh()->two_factor_secret);
    }

    #[Test]
    public function it_refuses_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get(route('settings.twoFactor.new'));

        $response->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_turns_two_factor_authentication_on(): void
    {
        Queue::fake();

        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->actingAs($user)->post(route('settings.twoFactor.create'), [
            'code' => $google2fa->getCurrentOtp($secret),
        ]);

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status', 'Two factor authentication is on.');

        $this->assertTrue($user->refresh()->usesTwoFactorAuthentication());
    }

    #[Test]
    public function it_says_so_when_the_code_is_not_right(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'two_factor_secret' => new Google2FA()->generateSecretKey(),
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->actingAs($user)->post(route('settings.twoFactor.create'), [
            'code' => '000000',
        ]);

        $response->assertSessionHasErrors('code');

        $this->assertFalse($user->refresh()->usesTwoFactorAuthentication());
    }

    #[Test]
    public function it_asks_for_a_code(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.twoFactor.create'), []);

        $response->assertSessionHasErrors('code');
    }

    #[Test]
    public function it_turns_two_factor_authentication_off(): void
    {
        Queue::fake();

        $user = User::factory()->twoFactor()->create();

        $response = $this->actingAs($user)->delete(route('settings.twoFactor.destroy'));

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status', 'Two factor authentication is off.');

        $user->refresh();

        $this->assertFalse($user->usesTwoFactorAuthentication());
        $this->assertNull($user->two_factor_secret);
    }
}
