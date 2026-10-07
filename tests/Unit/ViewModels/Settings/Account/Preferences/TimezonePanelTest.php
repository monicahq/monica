<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Preferences;

use App\Enums\TimeFormatEnum;
use App\Helpers\TimezoneCatalogue;
use App\Models\User;
use App\ViewModels\Settings\Account\Preferences\TimezonePanel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TimezonePanelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_stored_zone(): void
    {
        $panel = new TimezonePanel(user: User::factory()->create(['timezone' => 'Asia/Tokyo']));

        $this->assertSame('Asia/Tokyo', $panel->timezone());
    }

    #[Test]
    public function it_writes_how_far_the_zone_runs_from_utc(): void
    {
        CarbonImmutable::setTestNow('2026-01-15 12:00:00');

        $panel = new TimezonePanel(user: User::factory()->create(['timezone' => 'Asia/Kolkata']));

        $this->assertSame('+05:30', $panel->offset());
    }

    #[Test]
    public function it_follows_the_zone_into_summer_time(): void
    {
        CarbonImmutable::setTestNow('2026-01-15 12:00:00');
        $winter = new TimezonePanel(user: User::factory()->create(['timezone' => 'Europe/Paris']))->offset();

        CarbonImmutable::setTestNow('2026-07-15 12:00:00');
        $summer = new TimezonePanel(user: User::factory()->create(['timezone' => 'Europe/Paris']))->offset();

        $this->assertSame('+01:00', $winter);
        $this->assertSame('+02:00', $summer);
    }

    #[Test]
    public function it_reads_the_clock_where_somebody_is(): void
    {
        CarbonImmutable::setTestNow('2026-01-15 12:00:00');

        $panel = new TimezonePanel(user: User::factory()->create([
            'timezone' => 'Asia/Tokyo',
            'time_format' => TimeFormatEnum::TwentyFourHour,
        ]));

        $this->assertSame('21:00', $panel->clock());
    }

    #[Test]
    public function it_reads_the_clock_the_way_the_account_writes_a_time(): void
    {
        CarbonImmutable::setTestNow('2026-01-15 12:00:00');

        $panel = new TimezonePanel(user: User::factory()->create([
            'timezone' => 'Asia/Tokyo',
            'time_format' => TimeFormatEnum::TwelveHour,
        ]));

        $this->assertSame('9:00 PM', $panel->clock());
    }

    #[Test]
    public function it_names_the_place_the_zone_stands_for(): void
    {
        $panel = new TimezonePanel(user: User::factory()->create(['timezone' => 'America/Argentina/Buenos_Aires']));

        $this->assertSame('Buenos Aires, Argentina', $panel->place());
        $this->assertStringContainsString('Buenos Aires, Argentina', $panel->sentence());
    }

    #[Test]
    public function it_names_no_place_for_a_zone_belonging_to_no_country(): void
    {
        $panel = new TimezonePanel(user: User::factory()->create(['timezone' => 'UTC']));

        $this->assertSame('', $panel->place());
        $this->assertStringContainsString('UTC', $panel->sentence());
    }

    #[Test]
    public function it_hands_the_picker_the_latitudes_the_map_is_cut_off_at(): void
    {
        $projection = new TimezonePanel(user: User::factory()->create())->projection();

        $this->assertSame(TimezoneCatalogue::MAP_NORTH, $projection['north']);
        $this->assertSame(TimezoneCatalogue::MAP_SOUTH, $projection['south']);
    }

    #[Test]
    public function the_latitudes_it_hands_over_are_the_ones_the_marker_is_drawn_with(): void
    {
        $panel = new TimezonePanel(user: User::factory()->create());
        $projection = $panel->projection();

        $this->assertSame(0.0, TimezoneCatalogue::position($projection['north'], 0.0)['y']);
        $this->assertSame(100.0, TimezoneCatalogue::position($projection['south'], 0.0)['y']);
    }

    #[Test]
    public function it_places_the_marker_where_the_zone_is(): void
    {
        $greenwich = new TimezonePanel(user: User::factory()->create(['timezone' => 'Europe/London']))->marker();
        $tokyo = new TimezonePanel(user: User::factory()->create(['timezone' => 'Asia/Tokyo']))->marker();

        $this->assertEqualsWithDelta(50.0, $greenwich['x'], 0.5);
        $this->assertGreaterThan($greenwich['x'], $tokyo['x']);
    }

    #[Test]
    public function it_snaps_the_hour_band_to_the_fifteen_degree_grid(): void
    {
        $band = new TimezonePanel(user: User::factory()->create(['timezone' => 'Europe/London']))->band();

        $this->assertSame(47.9167, $band['left']);
        $this->assertSame(4.1667, $band['width']);
    }

    #[Test]
    public function it_snaps_a_city_off_the_grid_to_the_hour_it_belongs_to(): void
    {
        $band = new TimezonePanel(user: User::factory()->create(['timezone' => 'Asia/Tokyo']))->band();

        $this->assertSame(85.4167, $band['left']);
        $this->assertSame(4.1667, $band['width']);
    }

    #[Test]
    public function it_clips_the_hour_band_that_straddles_the_antimeridian(): void
    {
        $band = new TimezonePanel(user: User::factory()->create(['timezone' => 'Pacific/Auckland']))->band();

        $this->assertGreaterThanOrEqual(0.0, $band['left']);
        $this->assertLessThanOrEqual(100.0, $band['left'] + $band['width']);
    }

    #[Test]
    public function it_says_nothing_about_a_guess_unless_the_zone_came_from_one(): void
    {
        $named = new TimezonePanel(user: User::factory()->create(['timezone' => 'Europe/Paris']));
        $clicked = new TimezonePanel(user: User::factory()->create(['timezone' => 'Europe/Paris']), isGuess: true);

        $this->assertFalse($named->isGuess());
        $this->assertTrue($clicked->isGuess());
    }

    #[Test]
    public function it_offers_every_zone_with_the_time_it_is_there(): void
    {
        CarbonImmutable::setTestNow('2026-01-15 12:00:00');

        $zones = new TimezonePanel(user: User::factory()->create(['timezone' => 'Europe/Paris']))->zones();
        $tokyo = collect($zones)->firstWhere('timezone', 'Asia/Tokyo');

        $this->assertCount(419, $zones);
        $this->assertSame('Tokyo, Japan', $tokyo['name']);
        $this->assertSame('+09:00', $tokyo['offset']);
        $this->assertSame('21:00', $tokyo['clock']);
        $this->assertSame('tokyo japan asia/tokyo', $tokyo['search']);
    }

    #[Test]
    public function it_hands_every_row_the_line_the_search_matches_on(): void
    {
        $zones = new TimezonePanel(user: User::factory()->create(['timezone' => 'UTC']))->zones();

        $this->assertSame(
            'paris france europe/paris',
            collect($zones)->firstWhere('timezone', 'Europe/Paris')['search'],
        );
        $this->assertCount(419, collect($zones)->pluck('search')->filter()->all());
    }
}
