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
            'street' => '123 main st',
            'city' => 'montreal',
            'province' => 'quebec',
            'postal_code' => 'h1k 12k',
            'country' => 'Canada',
        ]);

        $this->assertEquals(
            'https://www.google.com/maps/place/123+main+st+montreal+quebec+h1k+12k+Canada',
            MapHelper::getMapLink($address, $user)
        );

        $user = User::factory()->create([
            'default_map_site' => User::MAPS_SITE_OPEN_STREET_MAPS,
        ]);
        $this->assertEquals(
            'https://www.openstreetmap.org/search?query=123+main+st+montreal+quebec+h1k+12k+Canada',
            MapHelper::getMapLink($address, $user)
        );
    }

    /** @test */
    public function it_gets_the_address_as_a_string(): void
    {
        $address = Address::factory()->create([
            'street' => '123 main st',
            'city' => 'montreal',
            'province' => 'quebec',
            'postal_code' => 'h1k 12k',
            'country' => 'Canada',
        ]);

        $this->assertEquals(
            '123 main st montreal quebec h1k 12k Canada',
            MapHelper::getAddressAsString($address)
        );
    }
}
