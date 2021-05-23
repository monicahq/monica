<?php

namespace Tests\Unit\Services\Instance\Weather;

use Tests\TestCase;
use App\Models\Account\Place;
use App\Models\Account\Account;
use App\Models\Account\Weather;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use App\Exceptions\MissingEnvVariableException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Instance\Weather\GetWeatherInformation;

class GetWeatherInformationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_weather_information()
    {
        $place = factory(Place::class)->create([
            'latitude' => '34.112456',
            'longitude' => '-118.4270732',
        ]);

        config(['monica.enable_weather' => true]);
        config(['monica.darksky_api_key' => 'test']);

        $body = file_get_contents(base_path('tests/Fixtures/Services/Instance/Weather/GetWeatherInformationSampleResponse.json'));
        Http::fake([
            'api.darksky.net/forecast/*' => Http::response($body, 200),
        ]);

        $request = [
            'place_id' => $place->id,
        ];

        $weather = app(GetWeatherInformation::class)->execute($request);

        $this->assertDatabaseHas('weather', [
            'id' => $weather->id,
            'account_id' => $place->account_id,
            'place_id' => $place->id,
        ]);

        $this->assertEquals(
            'Partly Cloudy',
            $weather->summary
        );

        $this->assertInstanceOf(
            Weather::class,
            $weather
        );
    }

    /** @test */
    public function it_gets_weather_information_for_new_place()
    {
        $account = factory(Account::class)->create();
        $place = factory(Place::class)->create([
            'account_id' => $account->id,
        ]);

        config(['monica.enable_weather' => true]);
        config(['monica.darksky_api_key' => 'test']);
        config(['monica.enable_geolocation' => true]);
        config(['monica.location_iq_api_key' => 'test']);

        $body = file_get_contents(base_path('tests/Fixtures/Services/Instance/Weather/GetWeatherInformationSampleResponse.json'));
        $placeBody = file_get_contents(base_path('tests/Fixtures/Services/Account/Place/CreatePlaceSampleResponse.json'));
        Http::fake([
            'us1.locationiq.com/v1/*' => Http::response($placeBody, 200),
            'api.darksky.net/forecast/*' => Http::response($body, 200),
        ]);

        $request = [
            'place_id' => $place->id,
        ];

        $weather = app(GetWeatherInformation::class)->execute($request);

        $this->assertDatabaseHas('weather', [
            'id' => $weather->id,
            'account_id' => $account->id,
            'place_id' => $place->id,
        ]);
        $this->assertDatabaseHas('places', [
            'id' => $place->id,
            'account_id' => $account->id,
            'street' => '12',
            'latitude' => 34.0736204,
            'longitude' => -118.4003563,
        ]);

        $this->assertEquals(
            'Partly Cloudy',
            $weather->summary
        );

        $this->assertInstanceOf(
            Weather::class,
            $weather
        );
    }

    /** @test */
    public function it_cant_get_weather_info_if_weather_not_enabled()
    {
        $place = factory(Place::class)->create([
            'latitude' => '34.112456',
            'longitude' => '-118.4270732',
        ]);

        config(['monica.enable_weather' => false]);

        $request = [
            'place_id' => $place->id,
        ];

        $this->expectException(MissingEnvVariableException::class);
        app(GetWeatherInformation::class)->execute($request);
    }

    /** @test */
    public function it_cant_get_weather_info_if_darksky_api_key_not_provided()
    {
        $place = factory(Place::class)->create([
            'latitude' => '34.112456',
            'longitude' => '-118.4270732',
        ]);

        config(['monica.enable_weather' => true]);
        config(['monica.darksky_api_key' => null]);

        $request = [
            'place_id' => $place->id,
        ];

        $this->expectException(MissingEnvVariableException::class);
        app(GetWeatherInformation::class)->execute($request);
    }

    /** @test */
    public function it_cant_get_weather_info_if_latitude_longitude_are_null()
    {
        $place = factory(Place::class)->create([]);

        config(['monica.enable_weather' => true]);
        config(['monica.darksky_api_key' => 'test']);
        config(['monica.enable_geolocation' => false]);

        $request = [
            'place_id' => $place->id,
        ];

        $this->assertNull(app(GetWeatherInformation::class)->execute($request));
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        config(['monica.enable_weather' => true]);
        config(['monica.darksky_api_key' => 'test']);

        $request = [];

        $this->expectException(ValidationException::class);
        app(GetWeatherInformation::class)->execute($request);
    }
}
