<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\LocaleCollation;
use App\Helpers\TimezoneCatalogue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TimezoneCatalogueTest extends TestCase
{
    #[Test]
    public function it_leaves_out_the_deprecated_aliases(): void
    {
        $this->assertNull(TimezoneCatalogue::find('US/Pacific'));
        $this->assertNull(TimezoneCatalogue::find('Asia/Calcutta'));
    }

    #[Test]
    public function it_leaves_out_the_bare_offsets(): void
    {
        $offsets = array_filter(
            array_column(TimezoneCatalogue::all(), 'timezone'),
            fn (string $timezone): bool => str_starts_with($timezone, 'Etc/'),
        );

        $this->assertSame([], $offsets);
    }

    #[Test]
    public function it_names_the_city_a_reader_would_recognise(): void
    {
        $zone = TimezoneCatalogue::find('America/Argentina/Buenos_Aires');

        $this->assertSame('Buenos Aires', $zone['city']);
        $this->assertSame('Argentina', $zone['country']);
    }

    #[Test]
    public function it_names_the_country_in_the_language_being_read(): void
    {
        $this->assertNotSame(
            TimezoneCatalogue::find('Europe/Berlin', 'en')['country'],
            TimezoneCatalogue::find('Europe/Berlin', 'fr_FR')['country'],
        );
    }

    #[Test]
    public function it_orders_the_zones_by_city_the_way_the_language_orders(): void
    {
        $cities = array_column(TimezoneCatalogue::all(), 'city');
        $collator = LocaleCollation::getCollator('en');

        foreach (array_slice($cities, 1) as $index => $city) {
            $this->assertLessThanOrEqual(0, $collator->compare($cities[$index], $city));
        }
    }

    #[Test]
    public function it_orders_an_accented_city_where_the_language_puts_it(): void
    {
        $cities = array_column(TimezoneCatalogue::all('fr_FR'), 'city');

        $this->assertLessThan(
            array_search('Reykjavik', $cities, true),
            array_search('Rarotonga', $cities, true),
        );
    }

    #[Test]
    public function it_indexes_the_city_the_country_and_the_identifier_in_lower_case(): void
    {
        $index = TimezoneCatalogue::searchIndex();

        $this->assertSame('paris france europe/paris', $index['Europe/Paris']);
    }

    #[Test]
    public function it_finds_the_zone_a_click_lands_on(): void
    {
        $this->assertSame('Asia/Tokyo', TimezoneCatalogue::nearest(35.68, 139.69)['timezone']);
        $this->assertSame('Europe/Paris', TimezoneCatalogue::nearest(48.86, 2.35)['timezone']);
    }

    #[Test]
    public function it_measures_across_the_antimeridian_rather_than_around_the_world(): void
    {
        $this->assertSame('Pacific/Fiji', TimezoneCatalogue::nearest(-17.5, -179.0)['timezone']);
    }

    #[Test]
    public function it_places_a_position_in_the_map_as_a_percentage(): void
    {
        $this->assertSame(50.0, TimezoneCatalogue::position(0.0, 0.0)['x']);
        $this->assertSame(0.0, TimezoneCatalogue::position(84.0, -180.0)['x']);
        $this->assertSame(0.0, TimezoneCatalogue::position(84.0, -180.0)['y']);
        $this->assertSame(100.0, TimezoneCatalogue::position(-58.0, 180.0)['y']);
    }
}
