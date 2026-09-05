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
    public function it_answers_a_caller_who_announced_nothing_in_the_preference_on_their_account(): void
    {
        Sanctum::actingAs(User::factory()->create(['locale' => 'de_DE']));

        $this->announcingNothing()->json('GET', route('api.v1.me'))->assertOk();

        $this->assertSame('de_DE', App::getLocale());
    }

    #[Test]
    public function what_the_caller_announces_beats_the_preference_on_the_account(): void
    {
        Sanctum::actingAs(User::factory()->create(['locale' => 'de_DE']));

        $this->withHeader('Accept-Language', 'fr-FR')->json('GET', route('api.v1.me'))->assertOk();

        $this->assertSame('fr_FR', App::getLocale());
    }

    #[Test]
    public function a_caller_naming_a_language_and_no_region_reaches_the_one_locale_for_it(): void
    {
        Sanctum::actingAs(User::factory()->create(['locale' => 'de_DE']));

        $this->withHeader('Accept-Language', 'fr')->json('GET', route('api.v1.me'))->assertOk();

        $this->assertSame('fr_FR', App::getLocale());
    }

    #[Test]
    public function a_caller_naming_a_region_the_product_does_not_ship_reaches_its_language(): void
    {
        Sanctum::actingAs(User::factory()->create(['locale' => 'de_DE']));

        $this->withHeader('Accept-Language', 'fr-CH')->json('GET', route('api.v1.me'))->assertOk();

        $this->assertSame('fr_FR', App::getLocale());
    }

    #[Test]
    public function a_caller_naming_nothing_the_product_ships_falls_through_to_the_account(): void
    {
        Sanctum::actingAs(User::factory()->create(['locale' => 'de_DE']));

        $this->withHeader('Accept-Language', 'sv-SE')->json('GET', route('api.v1.me'))->assertOk();

        $this->assertSame('de_DE', App::getLocale());
    }

    #[Test]
    public function the_first_language_the_product_ships_wins_over_the_ones_ahead_of_it_that_it_does_not(): void
    {
        Sanctum::actingAs(User::factory()->create(['locale' => 'de_DE']));

        $this->withHeader('Accept-Language', 'sv-SE,fr;q=0.8')->json('GET', route('api.v1.me'))->assertOk();

        $this->assertSame('fr_FR', App::getLocale());
    }

    #[Test]
    public function a_caller_with_no_token_is_answered_in_the_language_they_announce(): void
    {
        $this->withHeader('Accept-Language', 'fr-FR')
            ->json('POST', route('api.v1.login'), [])
            ->assertUnprocessable();

        $this->assertSame('fr_FR', App::getLocale());
    }

    #[Test]
    public function a_caller_with_neither_a_token_nor_a_language_is_answered_in_the_fallback(): void
    {
        config(['app.locale' => 'en']);

        $this->announcingNothing()->json('POST', route('api.v1.login'), [])->assertUnprocessable();

        $this->assertSame('en', App::getLocale());
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
    public function a_caller_naming_portuguese_without_a_region_reaches_the_one_that_answers_for_it(): void
    {
        Sanctum::actingAs(User::factory()->create(['locale' => 'de_DE']));

        $this->withHeader('Accept-Language', 'pt')->json('GET', route('api.v1.me'))->assertOk();

        $this->assertSame('pt_BR', App::getLocale());
    }

    #[Test]
    public function a_caller_naming_european_portuguese_reaches_that_one(): void
    {
        Sanctum::actingAs(User::factory()->create(['locale' => 'de_DE']));

        $this->withHeader('Accept-Language', 'pt-PT')->json('GET', route('api.v1.me'))->assertOk();

        $this->assertSame('pt_PT', App::getLocale());
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

    #[Test]
    public function a_choice_kept_on_a_device_takes_no_part_in_answering_a_call(): void
    {
        Sanctum::actingAs(User::factory()->create(['locale' => 'de_DE']));

        $this->announcingNothing()
            ->withCookie(LocaleResolution::COOKIE_NAME, 'it_IT')
            ->json('GET', route('api.v1.me'))
            ->assertOk();

        $this->assertSame('de_DE', App::getLocale());
    }

    private function announcingNothing(): static
    {
        return $this->withHeader('Accept-Language', '');
    }
}
