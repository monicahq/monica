<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Helpers\LocaleResolution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiLocaleResolutionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function what_the_caller_announces_beats_the_preference_on_the_account(): void
    {
        Sanctum::actingAs(User::factory()->create(['locale' => 'de_DE']));

        $this->withHeader('Accept-Language', 'fr-FR')->json('GET', route('api.v1.me'))->assertOk();

        $this->assertSame('fr_FR', App::getLocale());
    }

    #[Test]
    public function it_returns_a_translated_message_in_the_language_the_caller_asked_for(): void
    {
        $this->withHeader('Accept-Language', 'fr-FR')
            ->json('POST', route('api.v1.register'), ['terms' => false])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.terms.0',
                'Vous devez accepter les conditions d’utilisation et la politique de confidentialité pour créer un compte.',
            );
    }

    #[Test]
    public function a_request_carrying_no_session_resolves_without_raising(): void
    {
        Sanctum::actingAs(User::factory()->create(['locale' => 'de_DE']));

        $response = $this->announcingNothing()->json('GET', route('api.v1.me'))->assertOk();

        $this->assertFalse($response->baseResponse->headers->has('Set-Cookie'));
    }

    #[Test]
    public function answering_a_call_keeps_nothing_on_the_callers_device(): void
    {
        Sanctum::actingAs(User::factory()->create(['locale' => 'de_DE']));

        $response = $this->withHeader('Accept-Language', 'fr-FR')
            ->json('GET', route('api.v1.me'))
            ->assertOk();

        $this->assertNull($response->getCookie(LocaleResolution::COOKIE_NAME, false));
    }

    private function announcingNothing(): static
    {
        return $this->withHeader('Accept-Language', '');
    }
}
