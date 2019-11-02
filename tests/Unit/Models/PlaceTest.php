<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Place;
use App\Models\Account\Weather;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PlaceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $place = factory(Place::class)->create([]);
        $this->assertTrue($place->account()->exists());
    }

    public function test_it_has_many_weathers()
    {
        $weather = factory(Weather::class)->create([]);
        $this->assertTrue($weather->place->weathers()->exists());
    }

    public function test_it_returns_the_full_address_as_a_string()
    {
        $place = factory(Place::class)->create([]);
        $this->assertEquals(
            '12 beverly hills 90210 United States',
            $place->getAddressAsString()
        );
    }

    public function test_it_returns_country_name()
    {
        $place = factory(Place::class)->create([]);
        $this->assertEquals(
            'United States',
            $place->getCountryName()
        );
    }

    public function test_it_returns_a_link_to_google_maps()
    {
        $place = factory(Place::class)->create([]);

        $this->assertEquals(
            'https://www.google.com/maps/place/'.urlencode($place->getAddressAsString()),
            $place->getGoogleMapAddress()
        );
    }

    public function test_it_returns_a_google_map_url_with_latitude_longitude()
    {
        $place = new Place;
        $place->latitude = 24.197611;
        $place->longitude = 120.780512;

        $this->assertEquals(
            'http://maps.google.com/maps?q=24.197611,120.780512',
            $place->getGoogleMapsAddressWithLatitude()
        );
    }
}
