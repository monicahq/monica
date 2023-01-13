<?php

namespace Tests\Unit\Domains\Contact\ManageContactAddresses\Services;

use App\Domains\Contact\ManageContactAddresses\Services\GetGPSCoordinate;
use App\Exceptions\EnvVariablesNotSetException;
use App\Models\Address;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GetGPSCoordinateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_null_if_geolocation_is_disabled()
    {
        $this->expectException(EnvVariablesNotSetException::class);
        config(['monica.location_iq_api_key' => null]);

        $address = Address::factory()->create();

        $request = [
            'address_id' => $address->id,
        ];

        (new GetGPSCoordinate)->execute($request);
    }

    /** @test */
    public function it_gets_gps_coordinates()
    {
        config(['monica.location_iq_api_key' => 'test']);

        $body = file_get_contents(base_path('tests/Fixtures/Services/Address/GetGPSCoordinateSampleResponse.json'));
        Http::fake([
            'us1.locationiq.com/v1/*' => Http::response($body, 200),
        ]);

        $address = Address::factory()->create();

        $request = [
            'address_id' => $address->id,
        ];

        $address = (new GetGPSCoordinate)->execute($request);

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'latitude' => '34.0736204',
            'longitude' => '-118.4003563',
        ]);

        $this->assertInstanceOf(
            Address::class,
            $address
        );
    }

    /** @test */
    public function it_returns_null_if_we_cant_make_the_call(): void
    {
        $this->expectException(HttpClientException::class);
        config(['monica.location_iq_api_key' => 'test']);

        $address = Address::factory()->create([
            'line_1' => '',
            'city' => 'sieklopekznqqq',
            'postal_code' => '',
        ]);

        $request = [
            'address_id' => $address->id,
        ];

        $address = (new GetGPSCoordinate)->execute($request);

        $this->assertNull($address);
    }

    /** @test */
    public function it_returns_null_if_address_is_garbage(): void
    {
        $this->expectException(HttpClientException::class);
        config(['monica.location_iq_api_key' => 'test']);

        $body = file_get_contents(base_path('tests/Fixtures/Services/Address/GetGPSCoordinateGarbageResponse.json'));
        Http::fake([
            'us1.locationiq.com/v1/*' => Http::response($body, 404),
        ]);

        $address = Address::factory()->create([
            'line_1' => '',
            'city' => 'sieklopekznqqq',
            'postal_code' => '',
        ]);

        $request = [
            'address_id' => $address->id,
        ];

        $address = (new GetGPSCoordinate)->execute($request);

        $this->assertNull($address);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'address_id' => 111,
        ];

        $this->expectException(ValidationException::class);
        (new GetGPSCoordinate)->execute($request);
    }
}
