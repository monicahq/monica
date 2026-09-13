<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_security_screen(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'password_changed_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('Security and access', escape: false);
        $response->assertSee('Password', escape: false);
        $response->assertSee('Current password', escape: false);
        $response->assertSee('Last changed 2 days ago', escape: false);
        $response->assertSee('Back to settings', escape: false);
    }

    #[Test]
    public function it_says_there_are_no_api_keys_when_none_have_been_made(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('API keys', escape: false);
        $response->assertSee('Active · no API keys', escape: false);
        $response->assertSee('No keys yet', escape: false);
    }

    #[Test]
    public function it_lists_the_api_keys(): void
    {
        $user = User::factory()->create();
        $user->createToken('Dundie awards bot');

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('Active · 1 API key', escape: false);
        $response->assertSee('Dundie awards bot', escape: false);
        $response->assertSee('never used', escape: false);
        $response->assertDontSee('No API keys yet', escape: false);
    }

    #[Test]
    public function it_leaves_out_the_date_when_the_password_was_never_changed(): void
    {
        $user = User::factory()->create(['password_changed_at' => null]);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Last changed', escape: false);
    }

    #[Test]
    public function it_offers_a_password_to_an_account_created_through_a_provider(): void
    {
        $user = User::factory()->singleSignOn()->create();

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('This account has no password.', escape: false);
        $response->assertSee('Set a password', escape: false);
        $response->assertDontSee('Current password', escape: false);
    }

    #[Test]
    public function it_names_the_provider_that_signs_the_account_in(): void
    {
        config([
            'monica.sso_installed' => ['github', 'google', 'facebook'],
            'services.google.client_id' => 'dunder-mifflin-scranton',
            'services.google.client_secret' => 'that-is-what-she-said',
            'services.google.redirect' => 'https://monica.test/sso/google/callback',
        ]);
        $user = User::factory()->singleSignOn()->create();

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('Google', escape: false);
        $response->assertSee('Signs you in.', escape: false);
    }

    #[Test]
    public function it_offers_to_turn_two_factor_authentication_on_when_it_is_off(): void
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => null]);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('Two-factor authentication', escape: false);
        $response->assertSee('Authenticator app', escape: false);
        $response->assertSee('Turn it on', escape: false);
        $response->assertDontSee('Recovery codes', escape: false);
    }

    #[Test]
    public function it_shows_no_recovery_code(): void
    {
        $user = User::factory()->twoFactor()->create(['two_factor_confirmed_at' => now()->subDays(3)]);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('Turned on 3 days ago', escape: false);
        $response->assertSee('Recovery codes', escape: false);
        $response->assertDontSee('scranton-1', escape: false);
        $response->assertDontSee('scranton-2', escape: false);
    }

    #[Test]
    public function it_says_how_many_recovery_codes_are_left(): void
    {
        $user = User::factory()->twoFactor()->create(['two_factor_recovery_codes' => ['scranton-1', 'scranton-2']]);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('2 recovery codes left. They were shown once, when they were made.', escape: false);
    }

    #[Test]
    public function it_says_so_when_every_recovery_code_has_been_used(): void
    {
        $user = User::factory()->twoFactor()->create(['two_factor_recovery_codes' => []]);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('You have used every code. Ask for new ones before you need them.', escape: false);
    }

    #[Test]
    public function it_redirects_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get(route('settings.security.index'));

        $response->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_refuses_to_remove_a_passkey_without_a_confirmed_password(): void
    {
        $user = User::factory()->create();
        $passkey = Passkey::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('passkey.destroy', $passkey));

        $response->assertRedirect(route('auth.confirmPassword.new'));

        $this->assertModelExists($passkey);
    }

    #[Test]
    public function it_removes_a_passkey_with_a_confirmed_password(): void
    {
        $user = User::factory()->create();
        $passkey = Passkey::factory()->create(['user_id' => $user->id]);

        $response = $this->withConfirmedPassword()->actingAs($user)->delete(route('passkey.destroy', $passkey));

        $response->assertRedirect(route('settings.security.index'));

        $this->assertModelMissing($passkey);
    }

    #[Test]
    public function it_forgets_the_confirmation_when_a_passkey_goes(): void
    {
        $user = User::factory()->create();
        $passkey = Passkey::factory()->create(['user_id' => $user->id]);

        $this->withConfirmedPassword()->actingAs($user)->delete(route('passkey.destroy', $passkey));

        $this->assertNull(session('auth.password_confirmed_at'));
    }

    #[Test]
    public function it_removes_a_passkey_of_an_account_with_no_password_without_asking(): void
    {
        $user = User::factory()->create(['password' => null]);
        $passkey = Passkey::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('passkey.destroy', $passkey));

        $response->assertRedirect(route('settings.security.index'));

        $this->assertModelMissing($passkey);
    }
}
