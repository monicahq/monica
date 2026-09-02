<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Preferences;

use App\Enums\DateFormatEnum;
use App\Enums\NumberFormatEnum;
use App\Enums\TimeFormatEnum;
use App\Helpers\LocaleResolution;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PreferenceControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_preferences_screen(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'locale' => 'en',
            'time_format' => TimeFormatEnum::TwentyFourHour,
        ]);

        $response = $this->actingAs($user)->get(route('settings.preferences.index'));

        $response->assertStatus(200);
        $response->assertSee('Preferences', escape: false);
        $response->assertSee('Language', escape: false);
        $response->assertSee('Time format', escape: false);
        $response->assertSee('24-hour', escape: false);
        $response->assertSee('Date format', escape: false);
        $response->assertSee('Month spelled out', escape: false);
        $response->assertSee('Number format', escape: false);
        $response->assertSee('Space and comma', escape: false);
        $response->assertSee('Follow the language', escape: false);
        $response->assertSee('Français', escape: false);
        $response->assertSee(route('settings.index'), escape: false);
        $response->assertSee('Back to settings', escape: false);
    }

    #[Test]
    public function it_shows_the_time_zone_section(): void
    {
        $user = User::factory()->create([
            'timezone' => 'Europe/Lisbon',
            'time_format' => TimeFormatEnum::TwentyFourHour,
        ]);

        $response = $this->actingAs($user)->get(route('settings.preferences.index'));

        $response->assertStatus(200);
        $response->assertSee('Time zone', escape: false);
        $response->assertSee('Europe/Lisbon', escape: false);
        $response->assertSee('Search a city or a country', escape: false);
        $response->assertSee(route('settings.timezone.update'), escape: false);
    }

    #[Test]
    public function it_offers_every_zone_as_something_to_pick(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        $response = $this->actingAs($user)->get(route('settings.preferences.index'));

        $response->assertSee('Asia/Tokyo', escape: false);
        $response->assertSee('America/Argentina/Buenos_Aires', escape: false);
        $response->assertSee('tokyo japan asia/tokyo', escape: false);
    }

    #[Test]
    public function it_reads_the_footer_preview_in_the_zone_the_account_is_in(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 31, 17, 45));

        $user = User::factory()->create([
            'timezone' => 'Asia/Tokyo',
            'time_format' => TimeFormatEnum::TwentyFourHour,
        ]);

        $response = $this->actingAs($user)->get(route('settings.preferences.index'));

        $response->assertSee('It is 02:45', escape: false);
    }

    #[Test]
    public function it_says_the_zone_was_the_nearest_one_when_it_came_from_the_map(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Tokyo']);

        $response = $this->actingAs($user)
            ->withSession(['timezone_guess' => true])
            ->get(route('settings.preferences.index'));

        $response->assertSee('nearest', escape: false);
    }

    #[Test]
    public function it_says_nothing_about_the_nearest_one_when_the_zone_was_named(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Tokyo']);

        $response = $this->actingAs($user)->get(route('settings.preferences.index'));

        $response->assertDontSee('nearest', escape: false);
    }

    #[Test]
    public function it_refuses_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get(route('settings.preferences.index'));

        $response->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_saves_the_preferences(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'locale' => 'en',
            'time_format' => TimeFormatEnum::TwentyFourHour,
        ]);

        $response = $this->actingAs($user)->put(route('settings.preferences.update'), [
            'locale' => 'fr_FR',
            'time_format' => '12',
        ]);

        $response->assertRedirect(route('settings.preferences.index'));

        // The message is written in the language that was just chosen, rather
        // than the one being left behind.
        $response->assertSessionHas('status', 'Vos préférences sont enregistrées.');

        $user->refresh();

        $this->assertEquals('fr_FR', $user->locale);
        $this->assertEquals(TimeFormatEnum::TwelveHour, $user->time_format);
    }

    #[Test]
    public function it_puts_the_new_language_in_the_session_so_the_screen_comes_back_in_it(): void
    {
        Queue::fake();

        $user = User::factory()->create(['locale' => 'en']);

        $response = $this->actingAs($user)->put(route('settings.preferences.update'), [
            'locale' => 'de_DE',
            'time_format' => '24',
        ]);

        $response->assertSessionHas('locale', 'de_DE');
        $response->assertCookie(LocaleResolution::COOKIE_NAME, 'de_DE');
    }

    #[Test]
    public function it_refuses_a_language_we_do_not_speak(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $response = $this->actingAs($user)->put(route('settings.preferences.update'), [
            'locale' => 'kl_GL',
            'time_format' => '24',
        ]);

        $response->assertSessionHasErrors('locale');
        $this->assertEquals('en', $user->refresh()->locale);
        $response->assertCookieMissing(LocaleResolution::COOKIE_NAME);
    }

    #[Test]
    public function it_refuses_a_time_format_that_does_not_exist(): void
    {
        $user = User::factory()->create(['time_format' => TimeFormatEnum::TwentyFourHour]);

        $response = $this->actingAs($user)->put(route('settings.preferences.update'), [
            'locale' => 'en',
            'time_format' => '36',
        ]);

        $response->assertSessionHasErrors('time_format');
        $this->assertEquals(TimeFormatEnum::TwentyFourHour, $user->refresh()->time_format);
    }

    #[Test]
    public function it_saves_the_date_format(): void
    {
        Queue::fake();

        $user = User::factory()->create(['date_format' => null]);

        $response = $this->actingAs($user)->put(route('settings.preferences.update'), [
            'locale' => 'en',
            'time_format' => '24',
            'date_format' => 'm/d/Y',
        ]);

        $response->assertRedirect(route('settings.preferences.index'));

        $this->assertEquals(DateFormatEnum::MonthFirst, $user->refresh()->date_format);
    }

    #[Test]
    public function it_hands_the_date_back_to_the_language_when_the_format_is_left_empty(): void
    {
        Queue::fake();

        $user = User::factory()->create(['date_format' => DateFormatEnum::MonthFirst]);

        $this->actingAs($user)->put(route('settings.preferences.update'), [
            'locale' => 'en',
            'time_format' => '24',
            'date_format' => '',
        ]);

        $this->assertNull($user->refresh()->date_format);
    }

    #[Test]
    public function it_refuses_a_date_format_that_does_not_exist(): void
    {
        $user = User::factory()->create(['date_format' => DateFormatEnum::DayFirst]);

        $response = $this->actingAs($user)->put(route('settings.preferences.update'), [
            'locale' => 'en',
            'time_format' => '24',
            'date_format' => 'the-day-of-the-beet-harvest',
        ]);

        $response->assertSessionHasErrors('date_format');
        $this->assertEquals(DateFormatEnum::DayFirst, $user->refresh()->date_format);
    }

    #[Test]
    public function it_keeps_the_other_formats_when_one_row_is_saved(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'date_format' => DateFormatEnum::YearFirst,
            'number_format' => NumberFormatEnum::NoSeparator,
        ]);

        $this->actingAs($user)->put(route('settings.preferences.update'), [
            'locale' => 'en',
            'time_format' => '12',
            'date_format' => 'Y-m-d',
            'number_format' => 'no_separator',
        ]);

        $user->refresh();

        $this->assertEquals(TimeFormatEnum::TwelveHour, $user->time_format);
        $this->assertEquals(DateFormatEnum::YearFirst, $user->date_format);
        $this->assertEquals(NumberFormatEnum::NoSeparator, $user->number_format);
    }

    #[Test]
    public function it_saves_the_number_format(): void
    {
        Queue::fake();

        $user = User::factory()->create(['number_format' => null]);

        $response = $this->actingAs($user)->put(route('settings.preferences.update'), [
            'locale' => 'en',
            'time_format' => '24',
            'number_format' => 'dot_comma',
        ]);

        $response->assertRedirect(route('settings.preferences.index'));

        $this->assertEquals(NumberFormatEnum::DotAndComma, $user->refresh()->number_format);
    }

    #[Test]
    public function it_hands_the_numbers_back_to_the_language_when_the_format_is_left_empty(): void
    {
        Queue::fake();

        $user = User::factory()->create(['number_format' => NumberFormatEnum::DotAndComma]);

        $this->actingAs($user)->put(route('settings.preferences.update'), [
            'locale' => 'en',
            'time_format' => '24',
            'number_format' => '',
        ]);

        $this->assertNull($user->refresh()->number_format);
    }

    #[Test]
    public function it_refuses_a_number_format_that_does_not_exist(): void
    {
        $user = User::factory()->create(['number_format' => NumberFormatEnum::CommaAndDot]);

        $response = $this->actingAs($user)->put(route('settings.preferences.update'), [
            'locale' => 'en',
            'time_format' => '24',
            'number_format' => 'beets_per_acre',
        ]);

        $response->assertSessionHasErrors('number_format');
        $this->assertEquals(NumberFormatEnum::CommaAndDot, $user->refresh()->number_format);
    }
}
