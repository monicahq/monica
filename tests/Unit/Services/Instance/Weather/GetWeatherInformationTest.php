<?php

namespace Tests\Unit\Services\Instance\Weather;

use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use App\Models\Account\Place;
use GuzzleHttp\Psr7\Response;
use App\Models\Account\Weather;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Validation\ValidationException;
use App\Exceptions\MissingEnvVariableException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Instance\Weather\GetWeatherInformation;

class GetWeatherInformationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_gets_weather_information()
    {
        $place = factory(Place::class)->create([
            'latitude' => '34.112456',
            'longitude' => '-118.4270732',
        ]);

        config(['monica.enable_weather' => true]);
        config(['monica.darksky_api_key' => 'test']);

        $body = file_get_contents(base_path('tests/Fixtures/Services/Instance/Weather/GetWeatherInformationSampleResponse.json'));
        $mock = new MockHandler([new Response(200, [], $body)]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $request = [
            'place_id' => $place->id,
        ];

        $weather = app(GetWeatherInformation::class)->execute($request, $client);

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

    public function test_it_cant_get_weather_info_if_weather_not_enabled()
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

    public function test_it_cant_get_weather_info_if_darksky_api_key_not_provided()
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

    public function test_it_cant_get_weather_info_if_latitude_longitude_are_null()
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

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        config(['monica.enable_weather' => true]);
        config(['monica.darksky_api_key' => 'test']);

        $request = [];

        $this->expectException(ValidationException::class);
        app(GetWeatherInformation::class)->execute($request);
    }
}
