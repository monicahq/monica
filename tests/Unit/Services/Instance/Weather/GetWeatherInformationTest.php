<?php

namespace Tests\Unit\Services\Instance\Weather;

use Tests\TestCase;
use App\Models\Account\Weather;
use App\Exceptions\MissingParameterException;
use App\Services\Weather\GetWeatherInformation;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GetWeatherInformationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_gets_weather_information()
    {
        $request = [
            'latitude' => '45.487685',
            'longitude' => '-73.590259',
        ];

        $weatherService = new GetWeatherInformation;
        $weather = $weatherService->execute($request);

        $this->assertDatabaseHas('weather', [
            'id' => $weather->id,
        ]);

        $this->assertInstanceOf(
            Weather::class,
            $weather
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'latitude' => '45.487685',
        ];

        $this->expectException(MissingParameterException::class);

        $weatherService = new GetWeatherInformation;
        $weatherService->execute($request);
    }
}
