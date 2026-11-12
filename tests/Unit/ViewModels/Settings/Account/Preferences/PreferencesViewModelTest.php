<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Preferences;

use App\Enums\DateFormatEnum;
use App\Enums\NumberFormatEnum;
use App\Enums\TimeFormatEnum;
use App\Helpers\AvailableLocales;
use App\Models\User;
use App\ViewModels\Settings\Account\Preferences\PreferencesViewModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PreferencesViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_every_language_and_ticks_the_one_in_use(): void
    {
        app()->setLocale('fr_FR');

        $user = User::factory()->create(['locale' => 'fr_FR']);

        $viewModel = new PreferencesViewModel(user: $user);

        $locales = $viewModel->locales();

        $this->assertCount(count(AvailableLocales::codes()), $locales);
        $this->assertEquals('fr_FR', $viewModel->locale());
        $this->assertEquals('Français', $viewModel->localeLabel());
        $this->assertTrue(collect($locales)->firstWhere('value', 'fr_FR')['selected']);
        $this->assertFalse(collect($locales)->firstWhere('value', 'en')['selected']);
    }

    #[Test]
    public function it_falls_back_to_the_language_of_the_application_when_the_one_in_use_is_unknown(): void
    {
        app()->setLocale('kl_GL');

        $user = User::factory()->create(['locale' => null]);

        $viewModel = new PreferencesViewModel(user: $user);

        $this->assertEquals(config('app.locale'), $viewModel->locale());
    }

    #[Test]
    public function it_lists_both_time_formats_and_ticks_the_one_in_use(): void
    {
        $user = User::factory()->create(['time_format' => TimeFormatEnum::TwelveHour]);

        $viewModel = new PreferencesViewModel(user: $user);

        $formats = $viewModel->timeFormats();

        $this->assertCount(2, $formats);
        $this->assertEquals(TimeFormatEnum::TwelveHour, $viewModel->timeFormat());
        $this->assertTrue(collect($formats)->firstWhere('value', '12')['selected']);
        $this->assertFalse(collect($formats)->firstWhere('value', '24')['selected']);
    }

    #[Test]
    public function it_writes_the_time_the_way_the_account_asked_for(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 31, 17, 45));

        $user = User::factory()->create(['time_format' => TimeFormatEnum::TwelveHour]);

        $viewModel = new PreferencesViewModel(user: $user);

        $this->assertEquals('5:45 PM', $viewModel->timePreview());
    }

    #[Test]
    public function it_writes_the_time_where_the_account_is_rather_than_where_the_instance_is(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 31, 17, 45));

        $user = User::factory()->create([
            'time_format' => TimeFormatEnum::TwentyFourHour,
            'timezone' => 'Asia/Tokyo',
        ]);

        $viewModel = new PreferencesViewModel(user: $user);

        $this->assertEquals('02:45', $viewModel->timePreview());
    }

    #[Test]
    public function it_writes_the_date_where_the_account_is_rather_than_where_the_instance_is(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 31, 17, 45));

        $user = User::factory()->create([
            'date_format' => DateFormatEnum::YearFirst,
            'timezone' => 'Asia/Tokyo',
        ]);

        $viewModel = new PreferencesViewModel(user: $user);

        $this->assertEquals('2026-08-01', $viewModel->datePreview());
    }

    #[Test]
    public function it_hands_the_time_zone_section_its_own_block(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Lisbon']);

        $viewModel = new PreferencesViewModel(user: $user);

        $this->assertSame('Europe/Lisbon', $viewModel->timezone()->timezone());
        $this->assertFalse($viewModel->timezone()->isGuess());
        $this->assertTrue($viewModel->timezone(isGuess: true)->isGuess());
    }

    #[Test]
    public function it_lists_every_date_format_and_ticks_the_one_in_use(): void
    {
        $user = User::factory()->create(['date_format' => DateFormatEnum::MonthFirst]);

        $viewModel = new PreferencesViewModel(user: $user);

        $formats = $viewModel->dateFormats();

        $this->assertCount(count(DateFormatEnum::cases()) + 1, $formats);
        $this->assertEquals(DateFormatEnum::MonthFirst, $viewModel->dateFormat());
        $this->assertTrue(collect($formats)->firstWhere('value', 'm/d/Y')['selected']);
        $this->assertFalse(collect($formats)->firstWhere('value', '')['selected']);
    }

    #[Test]
    public function it_offers_the_way_back_to_the_language_and_ticks_it_when_nothing_was_chosen(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 31));
        app()->setLocale('de_DE');

        $user = User::factory()->create(['date_format' => null]);

        $viewModel = new PreferencesViewModel(user: $user);

        $followTheLanguage = collect($viewModel->dateFormats())->firstWhere('value', '');

        $this->assertNull($viewModel->dateFormat());
        $this->assertTrue($followTheLanguage['selected']);
        $this->assertEquals('21.07.2026', $followTheLanguage['hint']);
        $this->assertEquals('21.07.2026', $viewModel->dateFormatLabel());
    }

    #[Test]
    public function it_writes_the_date_the_way_the_account_asked_for(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 31));

        $user = User::factory()->create(['date_format' => DateFormatEnum::YearFirst]);

        $viewModel = new PreferencesViewModel(user: $user);

        $this->assertEquals('2026-07-31', $viewModel->datePreview());
        $this->assertEquals('2026-07-21', $viewModel->dateFormatLabel());
    }

    #[Test]
    public function it_writes_the_date_the_way_the_language_does_when_nothing_was_chosen(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 31));
        app()->setLocale('nl_NL');

        $user = User::factory()->create(['date_format' => null]);

        $viewModel = new PreferencesViewModel(user: $user);

        $this->assertEquals('31-07-2026', $viewModel->datePreview());
    }

    #[Test]
    public function it_lists_every_number_format_and_ticks_the_one_in_use(): void
    {
        $user = User::factory()->create(['number_format' => NumberFormatEnum::DotAndComma]);

        $viewModel = new PreferencesViewModel(user: $user);

        $formats = $viewModel->numberFormats();

        $this->assertCount(count(NumberFormatEnum::cases()) + 1, $formats);
        $this->assertEquals(NumberFormatEnum::DotAndComma, $viewModel->numberFormat());
        $this->assertEquals('1.234,56', $viewModel->numberFormatLabel());
        $this->assertTrue(collect($formats)->firstWhere('value', 'dot_comma')['selected']);
        $this->assertFalse(collect($formats)->firstWhere('value', '')['selected']);
    }

    #[Test]
    public function it_writes_numbers_the_way_the_language_does_when_nothing_was_chosen(): void
    {
        app()->setLocale('fr_FR');

        $user = User::factory()->create(['number_format' => null]);

        $viewModel = new PreferencesViewModel(user: $user);

        $followTheLanguage = collect($viewModel->numberFormats())->firstWhere('value', '');

        $this->assertNull($viewModel->numberFormat());
        $this->assertTrue($followTheLanguage['selected']);
        $this->assertEquals("1\u{00A0}234,56", $followTheLanguage['hint']);
        $this->assertEquals("1\u{00A0}234,56", $viewModel->numberFormatLabel());
    }
}
