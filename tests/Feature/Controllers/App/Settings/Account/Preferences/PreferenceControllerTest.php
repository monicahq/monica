<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Preferences;

use App\Enums\DateFormatEnum;
use App\Enums\NumberFormatEnum;
use App\Enums\TimeFormatEnum;
use App\Helpers\LocaleResolution;
use App\Models\User;
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
    public function it_hands_the_map_to_the_registered_picker_rather_than_writing_it_in_an_attribute(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        $response = $this->actingAs($user)->get(route('settings.preferences.index'));

        $response->assertSee('x-data="timezonePicker(JSON.parse(', escape: false);
        $response->assertDontSee('@js(', escape: false);
        $response->assertSee('north', escape: false);
        $response->assertDontSee('clientX', escape: false);
        $response->assertDontSee('toFixed', escape: false);
        $response->assertDontSee('resolvedOptions', escape: false);
    }

    #[Test]
    public function it_hands_the_zones_to_the_picker_rather_than_writing_a_row_for_each(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        $response = $this->actingAs($user)->get(route('settings.preferences.index'));

        $response->assertSee('tokyo japan asia', escape: false);
        $response->assertSee(':value="zone.timezone"', escape: false);
        $response->assertDontSee('value="Asia/Tokyo"', escape: false);
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
            'uses_readable_addresses' => '1',
        ]);

        $response->assertRedirect(route('settings.preferences.index'));

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
            'uses_readable_addresses' => '1',
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
            'uses_readable_addresses' => '1',
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
            'uses_readable_addresses' => '1',
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
            'uses_readable_addresses' => '1',
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
            'uses_readable_addresses' => '1',
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
            'uses_readable_addresses' => '1',
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
            'uses_readable_addresses' => '1',
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
            'uses_readable_addresses' => '1',
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
            'uses_readable_addresses' => '1',
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
            'uses_readable_addresses' => '1',
        ]);

        $response->assertSessionHasErrors('number_format');
        $this->assertEquals(NumberFormatEnum::CommaAndDot, $user->refresh()->number_format);
    }

    #[Test]
    public function it_offers_the_choice_of_what_an_address_is_written_with(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('settings.preferences.index'))
            ->assertStatus(200)
            ->assertSee('Links')
            ->assertSee('Readable names')
            ->assertSee('Identifiers');
    }

    #[Test]
    public function the_choice_is_operable_with_the_keyboard_alone_and_announced(): void
    {
        $user = User::factory()->create();

        $html = (string) $this->actingAs($user)->get(route('settings.preferences.index'))
            ->assertStatus(200)
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<button[^>]*name="uses_readable_addresses"[^>]*value="0"/',
            $html,
            'Each choice has to be a real control, so the keyboard reaches it and a screen reader announces it.'
        );
        $this->assertStringNotContainsString('onclick=', $html);
    }

    #[Test]
    public function it_saves_the_choice_of_what_an_address_is_written_with(): void
    {
        $user = User::factory()->create(['uses_readable_addresses' => true]);

        $this->actingAs($user)->put(route('settings.preferences.update'), [
            'locale' => 'en',
            'time_format' => '24',
            'uses_readable_addresses' => '0',
        ])->assertRedirect(route('settings.preferences.index'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'uses_readable_addresses' => false]);
    }

    #[Test]
    public function it_refuses_a_choice_that_is_neither(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('settings.preferences.update'), [
            'locale' => 'en',
            'time_format' => '24',
            'uses_readable_addresses' => 'beets',
        ])->assertSessionHasErrors('uses_readable_addresses');
    }
}
