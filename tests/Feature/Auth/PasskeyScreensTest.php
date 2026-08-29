<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\WebauthnKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What the two screens show about passkeys.
 *
 * The suite runs over http on a host that is not localhost, so the unavailable
 * branch is what renders by default. Every test that wants the control has to
 * ask for a secure context, which is worth knowing: it is the same condition a
 * real deployment has to meet.
 */
class PasskeyScreensTest extends TestCase
{
    use RefreshDatabase;

    private function secure(): self
    {
        URL::forceScheme('https');

        return $this;
    }

    #[Test]
    public function the_sign_in_screen_offers_a_passkey_over_a_secure_connection(): void
    {
        $response = $this->secure()->get(route('auth.login.new'));

        $response->assertStatus(200);
        $response->assertSee('Sign in with a passkey');
    }

    #[Test]
    public function the_sign_in_screen_offers_no_passkey_over_an_insecure_connection(): void
    {
        $response = $this->get(route('auth.login.new'));

        $response->assertStatus(200);
        $response->assertDontSee('Sign in with a passkey');
    }

    #[Test]
    public function the_security_screen_offers_to_add_a_passkey_over_a_secure_connection(): void
    {
        $user = User::factory()->create();

        $response = $this->secure()->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('Add a passkey');
        $response->assertSee('No passkeys yet');
    }

    #[Test]
    public function the_security_screen_says_why_passkeys_cannot_be_used_here(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('Passkeys need a secure connection');
        $response->assertDontSee('Add a passkey');
    }

    #[Test]
    public function the_security_screen_lists_a_passkey_that_has_never_been_used(): void
    {
        $user = User::factory()->create();
        WebauthnKey::factory()->create(['user_id' => $user->id, 'name' => 'Michael MacBook']);

        $response = $this->secure()->actingAs($user)->get(route('settings.security.index'));

        $response->assertSee('Michael MacBook');
        $response->assertSee('never used yet');
        $response->assertSee('Active · 1 passkey');
    }

    #[Test]
    public function the_security_screen_shows_no_passkey_from_another_account(): void
    {
        $user = User::factory()->create();
        WebauthnKey::factory()->create(['name' => 'Somebody else laptop']);

        $response = $this->secure()->actingAs($user)->get(route('settings.security.index'));

        $response->assertDontSee('Somebody else laptop');
    }

    /**
     * A Blade directive is not compiled inside the attribute of a component tag:
     * the component compiler takes the attributes before directives are handled,
     * so `@js(...)` lands in the page verbatim and Alpine chokes on the `@`. It
     * happened once, on both these screens, and the whole component silently
     * failed to initialise. This is the guard.
     */
    #[Test]
    public function neither_screen_embeds_an_uncompiled_blade_directive(): void
    {
        $this->secure();

        $this->get(route('auth.login.new'))->assertDontSee('@js(', escape: false);

        $this->actingAs(User::factory()->create())
            ->get(route('settings.security.index'))
            ->assertDontSee('@js(', escape: false);
    }

    #[Test]
    public function a_passkey_created_at_registration_is_listed_like_any_other(): void
    {
        $user = User::factory()->create(['password' => null]);
        WebauthnKey::factory()->create(['user_id' => $user->id, 'name' => 'Signed up with this']);

        $response = $this->secure()->actingAs($user)->get(route('settings.security.index'));

        $response->assertSee('Signed up with this');
        $response->assertSee('never used yet');
    }
}
