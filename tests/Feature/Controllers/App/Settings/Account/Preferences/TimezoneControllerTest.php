<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Preferences;

use App\Enums\DateFormatEnum;
use App\Enums\NumberFormatEnum;
use App\Enums\TimeFormatEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TimezoneControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sets_the_time_zone_somebody_named(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'UTC']);

        $response = $this->actingAs($user)->put(route('settings.timezone.update'), [
            'timezone' => 'Europe/Lisbon',
        ]);

        $response->assertRedirect(route('settings.preferences.index'));
        $response->assertSessionHas('status', 'Your time zone is saved.');
        $response->assertSessionHas('timezone_guess', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'timezone' => 'Europe/Lisbon',
        ]);
    }

    #[Test]
    public function it_sets_the_nearest_time_zone_to_a_point_on_the_map(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'UTC']);

        $response = $this->actingAs($user)->put(route('settings.timezone.update'), [
            'latitude' => 35.68,
            'longitude' => 139.69,
        ]);

        $response->assertRedirect(route('settings.preferences.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'timezone' => 'Asia/Tokyo',
        ]);
    }

    #[Test]
    public function it_says_a_zone_reached_by_pointing_was_a_guess(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'UTC']);

        $response = $this->actingAs($user)->put(route('settings.timezone.update'), [
            'latitude' => 48.86,
            'longitude' => 2.35,
        ]);

        $response->assertSessionHas('timezone_guess', true);
    }

    #[Test]
    public function it_refuses_a_zone_the_product_does_not_know(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);

        $response = $this->actingAs($user)->put(route('settings.timezone.update'), [
            'timezone' => 'Nowhere/Here',
        ]);

        $response->assertSessionHasErrors('timezone');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'timezone' => 'Europe/Paris',
        ]);
    }

    #[Test]
    public function it_refuses_a_deprecated_alias(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);

        $response = $this->actingAs($user)->put(route('settings.timezone.update'), [
            'timezone' => 'US/Pacific',
        ]);

        $response->assertSessionHasErrors('timezone');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'timezone' => 'Europe/Paris',
        ]);
    }

    #[Test]
    public function it_refuses_a_point_that_is_not_on_the_globe(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);

        $response = $this->actingAs($user)->put(route('settings.timezone.update'), [
            'latitude' => 120,
            'longitude' => 400,
        ]);

        $response->assertSessionHasErrors(['latitude', 'longitude']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'timezone' => 'Europe/Paris',
        ]);
    }

    #[Test]
    public function it_refuses_a_request_that_names_neither_a_zone_nor_a_point(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);

        $response = $this->actingAs($user)->put(route('settings.timezone.update'), []);

        $response->assertSessionHasErrors(['timezone', 'latitude']);
    }

    #[Test]
    public function it_refuses_a_latitude_with_no_longitude_beside_it(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);

        $response = $this->actingAs($user)->put(route('settings.timezone.update'), [
            'latitude' => 48.86,
        ]);

        $response->assertSessionHasErrors('longitude');
    }

    #[Test]
    public function it_leaves_the_language_and_the_formats_where_they_were(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'timezone' => 'UTC',
            'locale' => 'fr_FR',
            'time_format' => TimeFormatEnum::TwelveHour,
            'date_format' => DateFormatEnum::YearFirst,
            'number_format' => NumberFormatEnum::SpaceAndComma,
        ]);

        $this->actingAs($user)->put(route('settings.timezone.update'), [
            'timezone' => 'Asia/Tokyo',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'timezone' => 'Asia/Tokyo',
            'locale' => 'fr_FR',
            'time_format' => '12',
            'date_format' => 'Y-m-d',
            'number_format' => 'space_comma',
        ]);
    }

    #[Test]
    public function it_sets_the_zone_the_device_reports(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'UTC']);

        $response = $this->actingAs($user)->put(route('settings.timezone.update'), [
            'timezone' => 'America/Chicago',
        ]);

        $response->assertSessionHas('timezone_guess', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'timezone' => 'America/Chicago',
        ]);
    }

    #[Test]
    public function it_says_it_does_not_know_a_zone_a_device_reported(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);

        $response = $this->actingAs($user)->put(route('settings.timezone.update'), [
            'timezone' => 'Mars/Olympus_Mons',
        ]);

        $response->assertSessionHasErrors([
            'timezone' => 'That is not a time zone the product knows. Search for the nearest city instead.',
        ]);
    }

    #[Test]
    public function it_refuses_a_device_that_reported_nothing(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);

        $response = $this->actingAs($user)->put(route('settings.timezone.update'), [
            'timezone' => '',
        ]);

        $response->assertSessionHasErrors('timezone');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'timezone' => 'Europe/Paris',
        ]);
    }

    #[Test]
    public function it_sets_a_named_zone_when_the_map_fields_ride_along_empty(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'UTC']);

        $response = $this->actingAs($user)->put(route('settings.timezone.update'), [
            'timezone' => 'Asia/Hong_Kong',
            'latitude' => '',
            'longitude' => '',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('settings.preferences.index'));
        $response->assertSessionHas('timezone_guess', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'timezone' => 'Asia/Hong_Kong',
        ]);
    }

    #[Test]
    public function it_refuses_a_form_that_arrives_entirely_empty(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);

        $response = $this->actingAs($user)->put(route('settings.timezone.update'), [
            'timezone' => '',
            'latitude' => '',
            'longitude' => '',
        ]);

        $response->assertSessionHasErrors('timezone');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'timezone' => 'Europe/Paris',
        ]);
    }

    #[Test]
    public function it_sets_the_nearest_zone_when_the_map_posts_a_point_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'UTC']);

        $response = $this->actingAs($user)->put(route('settings.timezone.update'), [
            'latitude' => '22.2783',
            'longitude' => '114.1747',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('timezone_guess', true);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'timezone' => 'Asia/Hong_Kong',
        ]);
    }

    #[Test]
    public function it_refuses_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->put(route('settings.timezone.update'), ['timezone' => 'Asia/Tokyo']);

        $response->assertRedirect(route('auth.login.new'));
    }
}
