<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityKeyOptionsControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_asks_the_browser_for_any_kind_of_key(): void
    {
        URL::forceScheme('https');

        $response = $this->withConfirmedPassword()
            ->actingAs(User::factory()->create())
            ->getJson(route('settings.securityKeyOptions.show'));

        $response->assertOk();
        $response->assertJsonPath('options.authenticatorSelection.residentKey', 'discouraged');
        $response->assertJsonPath('options.authenticatorSelection.userVerification', 'discouraged');
        $this->assertIsString(session('securityKey.registrationOptions'));
    }

    #[Test]
    public function it_asks_somebody_with_a_password_to_confirm_it_first(): void
    {
        URL::forceScheme('https');

        $response = $this->actingAs(User::factory()->create())
            ->get(route('settings.securityKeyOptions.show'));

        $response->assertRedirect(route('auth.confirmPassword.new'));
    }

    #[Test]
    public function it_asks_nothing_of_an_account_without_a_password(): void
    {
        URL::forceScheme('https');

        $response = $this->actingAs(User::factory()->create(['password' => null]))
            ->getJson(route('settings.securityKeyOptions.show'));

        $response->assertOk();
    }

    #[Test]
    public function it_answers_not_found_over_an_insecure_connection(): void
    {
        URL::forceScheme('http');

        $response = $this->withConfirmedPassword()
            ->actingAs(User::factory()->create())
            ->getJson(route('settings.securityKeyOptions.show'));

        $response->assertNotFound();
    }
}
