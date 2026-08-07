<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Preferences;

use App\Enums\TimeFormatEnum;
use App\Models\Company;
use App\Models\Employee;
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
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'display_name' => null,
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'locale' => 'en',
            'time_format' => TimeFormatEnum::TwentyFourHour,
        ]);

        $response = $this->actingAs($user)->get(route('settings.preferences.index'));

        $response->assertStatus(200);
        $response->assertSee('Preferences', escape: false);
        $response->assertSee('Language', escape: false);
        $response->assertSee('Time format', escape: false);
        $response->assertSee('24-hour', escape: false);
        $response->assertSee('Français', escape: false);
        $response->assertSee('Michael Scott', escape: false);
        $response->assertSee('Dunder Mifflin', escape: false);
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
}
