<?php

namespace Tests\Unit\Services\Instance\Geolocalization;

use Tests\TestCase;
use App\Models\Account\Place;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Instance\Geolocalization\GetGPSCoordinate;

class GetGPSCoordinateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_returns_null_if_geolocation_is_disabled()
    {
        config(['monica.enable_geolocation' => false]);

        $place = factory(Place::class)->create();

        $request = [
            'account_id' => $place->account_id,
            'place_id' => $place->id,
        ];

        $geocodingService = new GetGPSCoordinate;
        $place = $geocodingService->execute($request);

        $this->assertNull($place);
    }

    public function test_it_gets_gps_coordinates()
    {
        config(['monica.enable_geolocation' => true]);
        config(['monica.location_iq_api_key' => 'test']);

        \VCR\VCR::turnOn();
        \VCR\VCR::configure()->setMode('none');
        \VCR\VCR::configure()->enableRequestMatchers(['url']);
        \VCR\VCR::insertCassette('geolocalization_service_gets_gps_coordinates.yml');

        $place = factory(Place::class)->create();

        $request = [
            'account_id' => $place->account_id,
            'place_id' => $place->id,
        ];

        $geocodingService = new GetGPSCoordinate;
        $place = $geocodingService->execute($request);

        $this->assertDatabaseHas('places', [
            'id' => $place->id,
        ]);

        $this->assertInstanceOf(
            Place::class,
            $place
        );

        \VCR\VCR::eject();
        \VCR\VCR::turnOff();
    }

    public function test_it_returns_null_if_address_is_garbage()
    {
        \VCR\VCR::turnOn();
        \VCR\VCR::configure()->setMode('none');
        \VCR\VCR::configure()->enableRequestMatchers(['method', 'url']);
        \VCR\VCR::insertCassette('geolocalization_service_returns_null_if_address_is_garbage.yml');

        config(['monica.enable_geolocation' => true]);
        config(['monica.location_iq_api_key' => 'test']);

        $place = factory(Place::class)->create([
            'country' => 'ewqr',
            'street' => '',
            'city' => 'sieklopekznqqq',
            'postal_code' => '',
        ]);

        $request = [
            'account_id' => $place->account_id,
            'place_id' => $place->id,
        ];

        $geocodingService = new GetGPSCoordinate;
        $place = $geocodingService->execute($request);

        $this->assertNull($place);

        \VCR\VCR::eject();
        \VCR\VCR::turnOff();
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 111,
        ];

        $this->expectException(MissingParameterException::class);

        $geocodingService = new GetGPSCoordinate;
        $place = $geocodingService->execute($request);
    }
}
