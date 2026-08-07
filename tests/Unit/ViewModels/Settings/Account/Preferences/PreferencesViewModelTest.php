<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Preferences;

use App\Enums\TimeFormatEnum;
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
    public function it_gives_the_person_who_is_signed_in(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        $viewModel = new PreferencesViewModel(user: $user);

        $this->assertTrue($user->is($viewModel->user()));
        $this->assertEquals('Michael Scott', $viewModel->user()->name);
    }

    #[Test]
    public function it_lists_every_language_and_ticks_the_one_in_use(): void
    {
        app()->setLocale('fr_FR');

        $user = User::factory()->create(['locale' => 'fr_FR']);

        $viewModel = new PreferencesViewModel(user: $user);

        $locales = $viewModel->locales();

        $this->assertCount(count(config('monica.locales')), $locales);
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
        $this->assertEquals('12-hour', $viewModel->timeFormatLabel());
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
}
