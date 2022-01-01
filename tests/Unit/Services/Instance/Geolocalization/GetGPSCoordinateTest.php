<?php

namespace Tests\Unit\Services\Instance\Geolocalization;

use Tests\TestCase;
use App\Models\Account\Place;
use Illuminate\Support\Facades\Http;
use App\Exceptions\RateLimitedSecondException;
use Illuminate\Validation\ValidationException;
use App\Exceptions\MissingEnvVariableException;
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

        $this->expectException(MissingEnvVariableException::class);
        app(GetGPSCoordinate::class)->execute($request);
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
        config(['monica.enable_geolocation' => true]);
        config(['monica.location_iq_api_key' => 'test']);

        $request = [
            'account_id' => 111,
        ];

        $this->expectException(ValidationException::class);

        app(GetGPSCoordinate::class)->execute($request);
    }

    /** @test */
    public function it_release_the_job_if_rate_limited_second()
    {
        config(['monica.enable_geolocation' => true]);
        config(['monica.location_iq_api_key' => 'test']);

        Http::fake([
            'us1.locationiq.com/v1/*' => Http::response('{"error":"Rate Limited Second"}', 429),
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

        $this->expectException(RateLimitedSecondException::class);
        app(GetGPSCoordinate::class)->execute($request);
    }
}
