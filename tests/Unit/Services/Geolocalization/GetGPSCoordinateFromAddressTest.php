<?php

namespace Tests\Unit\Services\Geolocalization;

use Tests\TestCase;
use App\Exceptions\MissingParameterException;
use App\Services\Geolocalization\GetGPSCoordinateFromAddress;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GetGPSCoordinateFromAddressTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_gets_gps_coordinates()
    {
        $request = [
            'street' => 'sdfsd',
            'city' => 'sdfsd',
            'province' => 'sdfsd',
            'postal_code' => 'sdfsd',
            'country' => 'sdfsd',
        ];

        $weatherService = new GetGPSCoordinateFromAddress;
        $weather = $weatherService->execute($request);

        // $this->assertDatabaseHas('weather', [
        //     'id' => $weather->id,
        // ]);

        // $this->assertInstanceOf(
        //     Weather::class,
        //     $weather
        // );
    }

    // public function test_it_fails_if_wrong_parameters_are_given()
    // {
    //     $request = [
    //         'latitude' => '45.487685',
    //     ];

    //     $this->expectException(MissingParameterException::class);

    //     $weatherService = new GetWeatherInformation;
    //     $weatherService->execute($request);
    // }
}
