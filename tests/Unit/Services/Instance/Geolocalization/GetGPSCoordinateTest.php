<?php

namespace Tests\Unit\Services\Instance\Geolocalization;

use Tests\TestCase;
use App\Models\Account\Place;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Instance\Geolocalization\GetGPSCoordinate;

class GetGPSCoordinateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_null_if_geolocation_is_disabled()
    {
        config(['monica.enable_geolocation' => false]);

        $place = factory(Place::class)->create();

        $request = [
            'account_id' => $place->account_id,
            'place_id' => $place->id,
        ];

        $place = app(GetGPSCoordinate::class)->execute($request);

        $this->assertNull($place);
    }

    /** @test */
    public function it_gets_gps_coordinates()
    {
        config(['monica.enable_geolocation' => true]);
        config(['monica.location_iq_api_key' => 'test']);

        $body = file_get_contents(base_path('tests/Fixtures/Services/Instance/Geolocalization/GetGPSCoordinateSampleResponse.json'));
        Http::fake([
            'us1.locationiq.com/v1/*' => Http::response($body, 200),
        ]);

        $place = factory(Place::class)->create();

        $request = [
            'account_id' => $place->account_id,
            'place_id' => $place->id,
        ];

        $place = app(GetGPSCoordinate::class)->execute($request);

        $this->assertDatabaseHas('places', [
            'id' => $place->id,
        ]);

        $this->assertInstanceOf(
            Place::class,
            $place
        );
    }

    /** @test */
    public function it_returns_null_if_address_is_garbage()
    {
        config(['monica.enable_geolocation' => true]);
        config(['monica.location_iq_api_key' => 'test']);

        $body = file_get_contents(base_path('tests/Fixtures/Services/Instance/Geolocalization/GetGPSCoordinateGarbageResponse.json'));
        Http::fake([
            'us1.locationiq.com/v1/*' => Http::response($body, 404),
        ]);

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

        $place = app(GetGPSCoordinate::class)->execute($request);

        $this->assertNull($place);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 111,
        ];

        $this->expectException(ValidationException::class);

        $geocodingService = new GetGPSCoordinate;
        $place = app(GetGPSCoordinate::class)->execute($request);
    }
}
