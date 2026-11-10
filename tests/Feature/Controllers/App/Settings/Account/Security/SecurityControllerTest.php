<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Enums\SsoProviderEnum;
use App\Models\Passkey;
use App\Models\ProviderConnection;
use App\Models\SecurityKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
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
    public function it_asks_an_account_with_no_password_to_confirm_before_removing_a_passkey(): void
    {
        $user = User::factory()->create(['password' => null]);
        $passkey = Passkey::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('passkey.destroy', $passkey));

        $response->assertRedirect(route('auth.confirmPassword.new'));

        $this->assertModelExists($passkey);
    }

    #[Test]
    public function it_lists_the_security_keys_and_offers_to_add_one(): void
    {
        URL::forceScheme('https');
        $user = User::factory()->create();
        SecurityKey::factory()->for($user)->create(['name' => 'Dundie key']);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertOk();
        $response->assertSee('Dundie key');
        $response->assertSee('Add a security key');
        $response->assertSee('Two-factor authentication turns off, and your recovery codes stop working.');
        $response->assertSee('Recovery codes');
    }

    #[Test]
    public function it_offers_no_way_to_add_a_security_key_over_an_insecure_connection(): void
    {
        URL::forceScheme('http');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertSee('Security keys need a secure connection');
        $response->assertDontSee('Add a security key');
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

    #[Test]
    public function it_lists_the_devices_signed_in_and_marks_the_one_in_hand(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $currentSessionId = Str::random(40);
        $this->insertSession(id: $currentSessionId, user: $user, userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36');
        $this->insertSession(id: Str::random(40), user: $user, userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1');

        $response = $this->actingAs($user)
            ->withCookie(config('session.cookie'), $currentSessionId)
            ->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('Where you are signed in', escape: false);
        $response->assertSee('Chrome on macOS', escape: false);
        $response->assertSee('Safari on iPhone', escape: false);
        $this->assertSame(1, substr_count($response->getContent(), 'This device'));
    }

    #[Test]
    public function it_offers_to_sign_out_every_device_but_the_one_in_hand(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $currentSessionId = Str::random(40);
        $otherSessionId = Str::random(40);
        $this->insertSession(id: $currentSessionId, user: $user, userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36');
        $this->insertSession(id: $otherSessionId, user: $user, userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1');

        $response = $this->actingAs($user)
            ->withCookie(config('session.cookie'), $currentSessionId)
            ->get(route('settings.security.index'));

        $response->assertSee('action="'.route('settings.sessions.destroy', $otherSessionId).'"', escape: false);
        $response->assertSee('Safari on iPhone is asked to sign in at its next request.', escape: false);
        $response->assertDontSee(route('settings.sessions.destroy', $currentSessionId), escape: false);
    }

    #[Test]
    public function it_offers_to_sign_out_everywhere_else_behind_the_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertSee('action="'.route('settings.otherSessions.destroy').'"', escape: false);
        $response->assertSee('form="sign-out-other-devices"', escape: false);
        $response->assertSee('name="password"', escape: false);
    }

    #[Test]
    public function it_says_the_devices_cannot_be_listed_when_sessions_are_kept_in_files(): void
    {
        config(['session.driver' => 'file']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('This instance keeps sessions where they cannot be listed.', escape: false);
        $response->assertDontSee('This device', escape: false);
    }

    private function insertSession(string $id, User $user, string $userAgent): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => $userAgent,
            'payload' => '',
            'last_activity' => now()->getTimestamp(),
        ]);
    }

    #[Test]
    public function it_still_offers_the_other_providers_once_one_is_connected(): void
    {
        config(['monica.sso_installed' => SsoProviderEnum::values()]);

        foreach (['github', 'google'] as $provider) {
            config([
                "services.{$provider}.client_id" => 'dunder-mifflin-scranton',
                "services.{$provider}.client_secret" => 'that-is-what-she-said',
                "services.{$provider}.redirect" => "https://monica.test/sso/{$provider}/callback",
            ]);
        }

        $user = User::factory()->has(ProviderConnection::factory())->create();

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('Signs you in.', escape: false);
        $response->assertSee(route('settings.sso.destroy', ['provider' => 'google']), escape: false);
        $response->assertSee(route('settings.sso.redirect', ['provider' => 'github']), escape: false);
    }
}
