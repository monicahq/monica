<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What each screen says when a passkey cannot be used here, and that no way in
 * is taken away when one can.
 */
class PasskeyAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Force the scheme rather than leaning on whatever `APP_URL` happens to be.
     * Passkeys are offered on a secure connection and withheld otherwise, so a
     * test that asserts either branch has to say which one it is in. Reading it
     * from configuration made these tests pass or fail according to the address
     * in a developer's own `.env`.
     */
    private function overHttp(): self
    {
        URL::forceScheme('http');

        return $this;
    }

    #[Test]
    public function the_sign_in_screen_says_why_and_offers_no_control(): void
    {
        $response = $this->overHttp()->get(route('auth.login.new'));

        $response->assertStatus(200);
        $response->assertSee('Passkeys need a secure connection');
        $response->assertDontSee('Sign in with a passkey');
    }

    #[Test]
    public function the_security_screen_says_why_and_offers_no_control(): void
    {
        $response = $this->overHttp()->actingAs(User::factory()->create())->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('Passkeys need a secure connection');
        $response->assertDontSee('Add a passkey');
    }

    #[Test]
    public function the_sign_in_screen_still_offers_a_password_and_a_link(): void
    {
        $response = $this->overHttp()->get(route('auth.login.new'));

        $response->assertSee('Password');
        $response->assertSee('Send me a link instead');
        $response->assertSee(route('auth.magicLink.new'), escape: false);
    }

    #[Test]
    public function the_sign_in_screen_still_offers_a_password_and_a_link_when_passkeys_work(): void
    {
        URL::forceScheme('https');

        $response = $this->get(route('auth.login.new'));

        $response->assertSee('Sign in with a passkey');
        $response->assertSee('Password');
        $response->assertSee('Send me a link instead');
    }
}
