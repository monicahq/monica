<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
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

    #[Test]
    public function it_asks_for_the_password_before_adding_a_passkey(): void
    {
        URL::forceScheme('https');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertSee('asksForConfirmation: true', escape: false);
    }

    #[Test]
    public function it_adds_a_passkey_to_an_account_with_no_password_without_asking(): void
    {
        URL::forceScheme('https');
        $user = User::factory()->create(['password' => null]);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertSee('asksForConfirmation: false', escape: false);
    }

    #[Test]
    public function it_says_a_passkey_skips_the_two_factor_code_where_passkeys_and_two_factor_are(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $sentence = 'Signing in with a passkey does not ask for the two-factor code, since the passkey already needs this device and your fingerprint, your face or your device PIN.';

        $response->assertSeeInOrder(['Passkeys', $sentence, 'Two-factor authentication', $sentence, 'API keys'], escape: false);
    }

    #[Test]
    public function it_moves_focus_through_the_removal_of_a_passkey_and_ties_the_warning_to_the_button(): void
    {
        URL::forceScheme('https');
        $user = User::factory()->create();
        $passkey = Passkey::factory()->create(['user_id' => $user->id, 'name' => 'Michael MacBook']);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertSee('x-ref="remove"', escape: false);
        $response->assertSee('confirming = true; $nextTick(() => $refs.keep.focus())', escape: false);
        $response->assertSee('x-ref="keep"', escape: false);
        $response->assertSee('confirming = false; $nextTick(() => $refs.remove.focus())', escape: false);
        $response->assertSee("id=\"passkey-warning-{$passkey->id}\"", escape: false);
        $response->assertSee("aria-describedby=\"passkey-warning-{$passkey->id}\"", escape: false);
    }

    #[Test]
    public function it_keeps_the_rename_form_open_with_the_error_when_the_name_is_refused(): void
    {
        URL::forceScheme('https');
        $user = User::factory()->create();
        $refused = Passkey::factory()->create(['user_id' => $user->id, 'name' => 'Michael MacBook']);
        Passkey::factory()->create(['user_id' => $user->id, 'name' => 'Dwight Phone']);

        $response = $this->withConfirmedPassword()
            ->actingAs($user)
            ->from(route('settings.security.index'))
            ->followingRedirects()
            ->put(route('settings.passkeys.update', $refused->id), ['name' => '']);

        $response->assertSee('The name field is required.', escape: false);
        $response->assertSee("aria-describedby=\"passkey-name-{$refused->id}-error\"", escape: false);
        $response->assertSee('aria-invalid="true"', escape: false);
        $this->assertSame(1, substr_count($response->getContent(), 'renaming: true'));
        $this->assertSame(1, substr_count($response->getContent(), 'aria-invalid="true"'));
    }
}
