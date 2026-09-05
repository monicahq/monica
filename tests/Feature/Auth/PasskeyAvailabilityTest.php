<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasskeyAvailabilityTest extends TestCase
{
    use RefreshDatabase;

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
