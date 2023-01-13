<?php

namespace Tests\Unit\Helpers;

use App\Helpers\MapHelper;
use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MapHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_map_link(): void
    {
        $user = User::factory()->create([
            'default_map_site' => User::MAPS_SITE_GOOGLE_MAPS,
        ]);
        $address = Address::factory()->create([
            'line_1' => '123 main st',
            'line_2' => 'Apartment 4',
            'city' => 'montreal',
            'province' => 'quebec',
            'postal_code' => 'h1k 12k',
            'country' => 'Canada',
        ]);

        $this->assertEquals(
            'https://www.google.com/maps/place/123+main+st+Apartment+4+montreal+quebec+h1k+12k+Canada',
            MapHelper::getMapLink($address, $user)
        );

        $user = User::factory()->create([
            'default_map_site' => User::MAPS_SITE_OPEN_STREET_MAPS,
        ]);
        $this->assertEquals(
            'https://www.openstreetmap.org/search?query=123+main+st+Apartment+4+montreal+quebec+h1k+12k+Canada',
            MapHelper::getMapLink($address, $user)
        );
    }

    /** @test */
    public function it_gets_the_address_as_a_string(): void
    {
        $address = Address::factory()->create([
            'line_1' => '123 main st',
            'line_2' => 'Apartment 4',
            'city' => 'montreal',
            'province' => 'quebec',
            'postal_code' => 'h1k 12k',
            'country' => 'Canada',
        ]);

        $this->assertEquals(
            '123 main st Apartment 4 montreal quebec h1k 12k Canada',
            MapHelper::getAddressAsString($address)
        );
    }

    /** @test */
    public function it_returns_a_static_map_url(): void
    {
        config(['monica.mapbox_api_key' => 'api_key']);
        config(['monica.mapbox_username' => 'test']);
        config(['monica.mapbox_custom_style_name' => 'style']);

        $address = Address::factory()->create([
            'longitude' => '-74.005941',
            'latitude' => '40.712784',
        ]);

        $url = MapHelper::getStaticImage($address, 300, 300, 7);

        $this->assertEquals(
            'https://api.mapbox.com/styles/v1/test/style/static/-74.005941,40.712784,7/300x300?access_token=api_key',
            $url
        );
    }

    /** @test */
    public function it_cant_return_a_map_without_the_api_key_env_variable(): void
    {
        config(['monica.mapbox_api_key' => null]);
        config(['monica.mapbox_username' => 'test']);

        $address = Address::factory()->create([
            'longitude' => '-74.005941',
            'latitude' => '40.712784',
        ]);

        $url = MapHelper::getStaticImage($address, 300, 300, 7);

        $this->assertNull($url);
    }

    /** @test */
    public function it_cant_return_a_map_without_the_username_env_variable(): void
    {
        config(['monica.mapbox_api_key' => 'api_key']);
        config(['monica.mapbox_username' => null]);

        $address = Address::factory()->create([
            'longitude' => '-74.005941',
            'latitude' => '40.712784',
        ]);

        $url = MapHelper::getStaticImage($address, 300, 300, 7);

        $this->assertNull($url);
    }
}
