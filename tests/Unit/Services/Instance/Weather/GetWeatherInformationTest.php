<?php

namespace Tests\Unit\Services\Instance\Weather;

use Tests\TestCase;
use App\Models\Account\Place;
use App\Models\Account\Weather;
use Illuminate\Support\Facades\Http;
use App\Exceptions\NoCoordinatesException;
use Illuminate\Validation\ValidationException;
use App\Exceptions\MissingEnvVariableException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Instance\Weather\GetWeatherInformation;

class GetWeatherInformationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_weather_information_normal()
    {
        $place = factory(Place::class)->create([
            'latitude' => '34.112456',
            'longitude' => '-118.4270732',
        ]);

        config(['monica.enable_weather' => true]);
        config(['monica.weatherapi_key' => 'test']);

        $body = file_get_contents(base_path('tests/Fixtures/Services/Instance/Weather/GetWeatherInformationSampleResponse.json'));
        Http::fake([
            'api.weatherapi.com/v1/*' => Http::response($body, 200),
        ]);

        $request = [
            'account_id' => $place->account_id,
            'place_id' => $place->id,
        ];

        $weather = app(GetWeatherInformation::class)->execute($request);

        $this->assertDatabaseHas('weather', [
            'id' => $weather->id,
            'account_id' => $place->account_id,
            'place_id' => $place->id,
        ]);

        $this->assertEquals(
            'Partly cloudy',
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
    public function it_cant_get_weather_info_if_weatherapi_key_not_provided()
    {
        $place = factory(Place::class)->create([
            'latitude' => '34.112456',
            'longitude' => '-118.4270732',
        ]);

        config(['monica.enable_weather' => true]);
        config(['monica.weatherapi_key' => null]);

        $request = [
            'account_id' => $place->account_id,
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
        config(['monica.weatherapi_key' => 'test']);
        config(['monica.enable_geolocation' => false]);

        $request = [
            'account_id' => $place->account_id,
            'place_id' => $place->id,
        ];

        $this->expectException(NoCoordinatesException::class);
        app(GetWeatherInformation::class)->execute($request);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        config(['monica.enable_weather' => true]);
        config(['monica.weatherapi_key' => 'test']);

        $request = [];

        $this->expectException(ValidationException::class);
        app(GetWeatherInformation::class)->execute($request);
    }
}
