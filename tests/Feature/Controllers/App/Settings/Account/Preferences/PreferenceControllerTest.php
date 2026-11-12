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
}
