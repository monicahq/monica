<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\LocaleResolution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocaleResolutionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_falls_back_to_the_language_of_the_installation(): void
    {
        config(['app.locale' => 'de_DE']);

        $this->assertSame('de_DE', LocaleResolution::fromApplication());
    }

    #[Test]
    public function it_records_a_choice_in_the_session_on_the_device_and_on_the_screen(): void
    {
        LocaleResolution::remember(locale: 'it_IT');

        $this->assertSame('it_IT', Session::get(LocaleResolution::SESSION_KEY));
        $this->assertSame('it_IT', App::getLocale());

        $queued = Cookie::getQueuedCookies();

        $this->assertCount(1, $queued);
        $this->assertSame(LocaleResolution::COOKIE_NAME, $queued[0]->getName());
        $this->assertSame('it_IT', $queued[0]->getValue());
    }

    #[Test]
    public function it_reads_a_choice_made_in_this_session(): void
    {
        Session::put(LocaleResolution::SESSION_KEY, 'nl_NL');

        $this->assertSame('nl_NL', LocaleResolution::fromSession());
    }

    #[Test]
    public function it_reads_nothing_from_a_session_holding_nothing(): void
    {
        $this->assertNull(LocaleResolution::fromSession());
    }

    #[Test]
    public function it_refuses_a_session_holding_a_language_the_product_does_not_ship(): void
    {
        Session::put(LocaleResolution::SESSION_KEY, 'sv_SE');

        $this->assertNull(LocaleResolution::fromSession());
    }

    #[Test]
    public function it_reads_the_preference_stored_against_the_signed_in_account(): void
    {
        $dwight = User::factory()->create(['locale' => 'de_DE']);

        $this->actingAs($dwight);

        $this->assertSame('de_DE', LocaleResolution::fromUser());
    }

    #[Test]
    public function it_reads_nothing_from_the_account_of_a_visitor_who_is_not_signed_in(): void
    {
        $this->assertNull(LocaleResolution::fromUser());
    }

    #[Test]
    public function it_refuses_an_account_holding_a_language_the_product_does_not_ship(): void
    {
        $michael = User::factory()->create(['locale' => 'sv_SE']);

        $this->actingAs($michael);

        $this->assertNull(LocaleResolution::fromUser());
    }

    #[Test]
    public function it_reads_a_choice_made_on_this_device_before(): void
    {
        $this->assertSame('tr_TR', $this->resolutionFor(cookies: [LocaleResolution::COOKIE_NAME => 'tr_TR']));
    }

    #[Test]
    public function it_reads_nothing_from_a_device_holding_nothing(): void
    {
        $this->assertNull($this->resolutionFor(cookies: []));
    }

    #[Test]
    public function it_refuses_a_device_holding_a_language_the_product_has_since_withdrawn(): void
    {
        $this->assertNull($this->resolutionFor(cookies: [LocaleResolution::COOKIE_NAME => 'sv_SE']));
    }

    /** The table in data-model.md, one case per row. */
    #[Test]
    #[DataProvider('announcedLanguages')]
    public function it_matches_what_a_device_announces_to_a_language_the_product_ships(string $header, ?string $expected): void
    {
        $this->assertSame($expected, LocaleResolution::fromAnnouncedLanguage($this->requestAnnouncing($header)));
    }

    /** @return array<string, array{0: string, 1: ?string}> */
    public static function announcedLanguages(): array
    {
        return [
            'a language and a region both shipped' => ['fr-FR', 'fr_FR'],
            'a language shipped, with no region named' => ['fr', 'fr_FR'],
            'a region not shipped, for a language that is' => ['fr-CH', 'fr_FR'],
            'brazilian portuguese, named' => ['pt-BR', 'pt_BR'],
            'european portuguese, named' => ['pt-PT', 'pt_PT'],
            'portuguese with no region, which one locale answers for' => ['pt', 'pt_BR'],
            'american english, reaching the one english shipped' => ['en-US', 'en'],
            'a language not shipped' => ['sv-SE', null],
            'a bare language not shipped' => ['sv', null],
            'case is not meaning' => ['FR-fr', 'fr_FR'],
            'a script the product does not ship' => ['zh-Hans-TW', null],
            'nothing announced at all' => ['', null],
            'a malformed header' => ['!!!', null],
        ];
    }

    #[Test]
    public function it_takes_the_first_announced_language_the_product_ships_and_skips_the_ones_it_does_not(): void
    {
        $request = $this->requestAnnouncing('sv-SE,fr;q=0.8,de;q=0.7');

        $this->assertSame('fr_FR', LocaleResolution::fromAnnouncedLanguage($request));
    }

    #[Test]
    public function it_reads_the_announced_languages_in_the_order_of_preference_the_device_gives(): void
    {
        $request = $this->requestAnnouncing('de;q=0.5,it;q=0.9');

        $this->assertSame('it_IT', LocaleResolution::fromAnnouncedLanguage($request));
    }

    #[Test]
    public function on_a_screen_a_choice_made_in_this_session_beats_the_account(): void
    {
        $jim = User::factory()->create(['locale' => 'de_DE']);
        $this->actingAs($jim);
        Session::put(LocaleResolution::SESSION_KEY, 'es_ES');

        $this->assertSame('es_ES', LocaleResolution::forWeb($this->requestAnnouncing('')));
    }

    #[Test]
    public function on_a_screen_the_account_beats_a_choice_made_on_the_device_before(): void
    {
        $pam = User::factory()->create(['locale' => 'de_DE']);
        $this->actingAs($pam);

        $request = $this->requestAnnouncing('', [LocaleResolution::COOKIE_NAME => 'it_IT']);

        $this->assertSame('de_DE', LocaleResolution::forWeb($request));
    }

    #[Test]
    public function on_a_screen_a_choice_made_on_the_device_beats_what_the_browser_announces(): void
    {
        $request = $this->requestAnnouncing('fr-FR', [LocaleResolution::COOKIE_NAME => 'it_IT']);

        $this->assertSame('it_IT', LocaleResolution::forWeb($request));
    }

    #[Test]
    public function on_a_screen_what_the_browser_announces_beats_the_language_of_the_installation(): void
    {
        config(['app.locale' => 'en']);

        $this->assertSame('fr_FR', LocaleResolution::forWeb($this->requestAnnouncing('fr-FR')));
    }

    #[Test]
    public function on_a_screen_the_language_of_the_installation_answers_when_nothing_else_does(): void
    {
        config(['app.locale' => 'nl_NL']);

        $this->assertSame('nl_NL', LocaleResolution::forWeb($this->requestAnnouncing('')));
    }

    #[Test]
    public function on_the_api_what_the_caller_announces_beats_the_account(): void
    {
        $angela = User::factory()->create(['locale' => 'de_DE']);
        $this->actingAs($angela);

        $this->assertSame('fr_FR', LocaleResolution::forApi($this->requestAnnouncing('fr-FR')));
    }

    #[Test]
    public function on_the_api_the_account_answers_a_caller_who_announced_nothing(): void
    {
        $oscar = User::factory()->create(['locale' => 'de_DE']);
        $this->actingAs($oscar);

        $this->assertSame('de_DE', LocaleResolution::forApi($this->requestAnnouncing('')));
    }

    #[Test]
    public function on_the_api_a_language_the_product_does_not_ship_falls_through_to_the_account(): void
    {
        $kevin = User::factory()->create(['locale' => 'de_DE']);
        $this->actingAs($kevin);

        $this->assertSame('de_DE', LocaleResolution::forApi($this->requestAnnouncing('sv-SE')));
    }

    #[Test]
    public function on_the_api_the_language_of_the_installation_answers_a_caller_with_neither(): void
    {
        config(['app.locale' => 'en']);

        $this->assertSame('en', LocaleResolution::forApi($this->requestAnnouncing('')));
    }

    #[Test]
    public function on_the_api_a_choice_kept_on_a_device_takes_no_part(): void
    {
        $creed = User::factory()->create(['locale' => 'de_DE']);
        $this->actingAs($creed);

        $request = $this->requestAnnouncing('', [LocaleResolution::COOKIE_NAME => 'it_IT']);

        $this->assertSame('de_DE', LocaleResolution::forApi($request));
    }

    /**
     * A request carrying an Accept-Language header, and whatever the device holds.
     *
     * @param  array<string, string>  $cookies
     */
    private function requestAnnouncing(string $header, array $cookies = []): Request
    {
        $request = Request::create('/', 'GET', [], $cookies, [], ['HTTP_ACCEPT_LANGUAGE' => $header]);

        $this->app->instance('request', $request);

        return $request;
    }

    /**
     * @param  array<string, string>  $cookies
     */
    private function resolutionFor(array $cookies): ?string
    {
        $this->requestAnnouncing('', $cookies);

        return LocaleResolution::fromCookie();
    }
}
