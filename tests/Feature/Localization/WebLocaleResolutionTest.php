<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Helpers\LocaleResolution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebLocaleResolutionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[DataProvider('screensOpenToAnybody')]
    public function it_puts_every_screen_in_the_readers_language_without_the_route_asking(string $route): void
    {
        $this->withSession([LocaleResolution::SESSION_KEY => 'fr_FR'])->get(route($route));

        $this->assertSame('fr_FR', App::getLocale());
    }

    /** @return array<string, array{0: string}> */
    public static function screensOpenToAnybody(): array
    {
        return [
            'the sign in screen' => ['auth.login.new'],
            'the registration screen' => ['auth.register.new'],
            'the api reference' => ['docs.api.index'],
        ];
    }

    #[Test]
    public function it_puts_a_signed_in_screen_in_the_readers_language(): void
    {
        $dwight = User::factory()->create(['locale' => 'de_DE']);

        $this->actingAs($dwight)->get(route('home.index'));

        $this->assertSame('de_DE', App::getLocale());
    }

    #[Test]
    public function it_shows_a_guest_screen_translated_and_not_merely_resolved(): void
    {
        $this->withSession([LocaleResolution::SESSION_KEY => 'fr_FR'])
            ->get(route('auth.register.new'))
            ->assertSee('Créez votre compte');
    }

    #[Test]
    public function a_choice_made_in_this_session_beats_the_account(): void
    {
        $jim = User::factory()->create(['locale' => 'de_DE']);

        $this->actingAs($jim)
            ->withSession([LocaleResolution::SESSION_KEY => 'es_ES'])
            ->get(route('home.index'));

        $this->assertSame('es_ES', App::getLocale());
    }

    #[Test]
    public function the_account_beats_a_choice_made_on_the_device_before(): void
    {
        $pam = User::factory()->create(['locale' => 'de_DE']);

        $this->actingAs($pam)
            ->withCookie(LocaleResolution::COOKIE_NAME, 'it_IT')
            ->get(route('home.index'));

        $this->assertSame('de_DE', App::getLocale());
    }

    #[Test]
    public function a_choice_made_on_the_device_beats_what_the_browser_announces(): void
    {
        $this->withCookie(LocaleResolution::COOKIE_NAME, 'it_IT')
            ->withHeader('Accept-Language', 'fr-FR')
            ->get(route('auth.login.new'));

        $this->assertSame('it_IT', App::getLocale());
    }

    #[Test]
    public function a_first_time_visitor_lands_in_the_language_their_browser_announces(): void
    {
        $this->withHeader('Accept-Language', 'fr-CH,fr;q=0.9,en;q=0.8')
            ->get(route('auth.register.new'))
            ->assertSee('Créez votre compte');

        $this->assertSame('fr_FR', App::getLocale());
    }

    #[Test]
    public function a_first_time_visitor_announcing_nothing_the_product_ships_lands_in_the_fallback(): void
    {
        config(['app.locale' => 'en']);

        $this->withHeader('Accept-Language', 'sv-SE,fi;q=0.8')->get(route('auth.login.new'));

        $this->assertSame('en', App::getLocale());
    }

    #[Test]
    public function a_session_holding_a_language_the_product_does_not_ship_is_skipped(): void
    {
        config(['app.locale' => 'en']);

        $this->withSession([LocaleResolution::SESSION_KEY => 'sv_SE'])->get(route('auth.login.new'));

        $this->assertSame('en', App::getLocale());
    }

    #[Test]
    public function a_device_holding_a_language_the_product_does_not_ship_is_skipped(): void
    {
        config(['app.locale' => 'en']);

        $this->withCookie(LocaleResolution::COOKIE_NAME, 'sv_SE')
            ->get(route('auth.login.new'));

        $this->assertSame('en', App::getLocale());
    }

    #[Test]
    public function it_keeps_nothing_on_the_device_of_a_visitor_who_never_chose_a_language(): void
    {
        $response = $this->withHeader('Accept-Language', 'fr-FR')->get(route('auth.login.new'));

        $this->assertNull($response->getCookie(LocaleResolution::COOKIE_NAME, false));
    }
}
