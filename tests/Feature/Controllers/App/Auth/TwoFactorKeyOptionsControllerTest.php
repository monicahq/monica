<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\SecurityKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TwoFactorKeyOptionsControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_asks_for_the_keys_of_the_person_whose_password_was_accepted(): void
    {
        URL::forceScheme('https');
        $user = User::factory()->create();
        $key = SecurityKey::factory()->for($user)->create();

        $response = $this->withSession(['twoFactor.user.id' => $user->id])
            ->getJson(route('auth.twoFactorKeyOptions.show'));

        $response->assertOk();
        $response->assertJsonPath('options.allowCredentials.0.id', $key->credential_id);
        $response->assertJsonPath('options.userVerification', 'discouraged');
        $this->assertIsString(session('twoFactor.keyOptions'));
    }

    #[Test]
    public function it_says_it_took_too_long_when_no_password_was_accepted(): void
    {
        URL::forceScheme('https');

        $response = $this->getJson(route('auth.twoFactorKeyOptions.show'));

        $response->assertUnprocessable();
        $response->assertJsonPath('redirect', route('auth.login.new'));
    }

    #[Test]
    public function it_answers_not_found_over_an_insecure_connection(): void
    {
        URL::forceScheme('http');
        $user = User::factory()->create();
        SecurityKey::factory()->for($user)->create();

        $response = $this->withSession(['twoFactor.user.id' => $user->id])
            ->getJson(route('auth.twoFactorKeyOptions.show'));

        $response->assertNotFound();
    }
}
