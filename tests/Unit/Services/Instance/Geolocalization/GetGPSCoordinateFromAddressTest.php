<?php

namespace Tests\Unit\Services\Instance\Geolocalization;

use Tests\TestCase;
use App\Models\Contact\Address;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Instance\Geolocalization\GetGPSCoordinateFromAddress;

class GetGPSCoordinateFromAddressTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_returns_null_if_geolocation_is_disabled()
    {
        config(['monica.enable_geolocation' => false]);

        $address = factory(Address::class)->create();

        $request = [
            'account_id' => $address->account_id,
            'address_id' => $address->id,
        ];

        $addressService = new GetGPSCoordinateFromAddress;
        $address = $addressService->execute($request);

        $this->assertNull($address);
    }

    public function test_it_gets_gps_coordinates()
    {
        config(['monica.enable_geolocation' => true]);
        config(['monica.location_iq_api_key' => 'test']);

        \VCR\VCR::turnOn();
        \VCR\VCR::configure()->setMode('none');
        \VCR\VCR::configure()->enableRequestMatchers(['url']);
        \VCR\VCR::insertCassette('geolocalization_service_gets_gps_coordinates.yml');

        $address = factory(Address::class)->create();

        $request = [
            'account_id' => $address->account_id,
            'address_id' => $address->id,
        ];

        $addressService = new GetGPSCoordinateFromAddress;
        $address = $addressService->execute($request);

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
        ]);

        $this->assertInstanceOf(
            Address::class,
            $address
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

        $address = factory(Address::class)->create([
            'country' => 'ewqr',
            'street' => '',
            'city' => 'sieklopekznqqq',
            'postal_code' => '',
        ]);

        $request = [
            'account_id' => $address->account_id,
            'address_id' => $address->id,
        ];

        $addressService = new GetGPSCoordinateFromAddress;
        $address = $addressService->execute($request);

        $this->assertNull($address);

        \VCR\VCR::eject();
        \VCR\VCR::turnOff();
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 111,
        ];

        $this->expectException(MissingParameterException::class);

        $addressService = new GetGPSCoordinateFromAddress;
        $address = $addressService->execute($request);
    }
}
