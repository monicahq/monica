<?php

namespace Tests\Unit\Domains\Vault\ManageAddresses\Services;

use App\Domains\Vault\ManageAddresses\Services\GetGPSCoordinate;
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

    public function setUp(): void
    {
        parent::setUp();

        config(['monica.location_iq_url' => 'https://fake.com/v1/']);
        config(['monica.location_iq_api_key' => 'test']);
    }

    /** @test */
    public function it_returns_null_if_geolocation_is_disabled()
    {
        $this->expectException(EnvVariablesNotSetException::class);
        config(['monica.location_iq_api_key' => null]);

        $address = Address::factory()->create();

        $request = [
            'address_id' => $address->id,
        ];

        (new GetGPSCoordinate($request))->handle();
    }

    /** @test */
    public function it_gets_gps_coordinates()
    {
        $body = file_get_contents(base_path('tests/Fixtures/Services/Address/GetGPSCoordinateSampleResponse.json'));
        Http::fake([
            'fake.com/v1/*' => Http::response($body, 200),
        ]);

        $address = Address::factory()->create();

        $request = [
            'address_id' => $address->id,
        ];

        (new GetGPSCoordinate($request))->handle();

        $address->refresh();

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
    public function it_fails_if_we_cant_make_the_call(): void
    {
        $this->expectException(HttpClientException::class);
        Http::fake([
            'fake.com/v1/*' => Http::response('{"error":"Invalid key"}', 401),
        ]);

        $address = Address::factory()->create([
            'line_1' => '',
            'city' => 'sieklopekznqqq',
            'postal_code' => '',
        ]);

        $request = [
            'address_id' => $address->id,
        ];

        (new GetGPSCoordinate($request))->handle();
    }

    /** @test */
    public function it_fails_if_address_is_unknown(): void
    {
        $this->expectException(HttpClientException::class);

        $body = file_get_contents(base_path('tests/Fixtures/Services/Address/GetGPSCoordinateGarbageResponse.json'));
        Http::fake([
            'fake.com/v1/*' => Http::response($body, 404),
        ]);

        $address = Address::factory()->create([
            'line_1' => '',
            'city' => 'sieklopekznqqq',
            'postal_code' => '',
        ]);

        $request = [
            'address_id' => $address->id,
        ];

        (new GetGPSCoordinate($request))->handle();
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'address_id' => 0,
        ];

        $this->expectException(ValidationException::class);
        (new GetGPSCoordinate($request))->handle();
    }
}
