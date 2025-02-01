<?php

namespace Tests\Unit\Domains\Vault\ManageAddresses\Services;

use App\Domains\Vault\ManageAddresses\Services\GetGPSCoordinate;
use App\Exceptions\EnvVariablesNotSetException;
use App\Models\Address;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GetGPSCoordinateTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
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

    public function fails_if_we_cant_make_the_call_callback(GetGPSCoordinate $job, \Exception $e)
    {
        $this->assertInstanceOf(HttpClientException::class, $e);
    }

    /** @test */
    public function it_fails_if_we_cant_make_the_call(): void
    {
        Http::fake([
            'fake.com/v1/*' => Http::response('{"error":"Invalid key"}', 401),
        ]);

        $address = Address::factory()->create([
            'line_1' => '',
            'city' => 'sieklopekznqqq',
            'postal_code' => '',
            'latitude' => null,
            'longitude' => null,
        ]);

        $request = [
            'address_id' => $address->id,
        ];

        $job = (new GetGPSCoordinate($request));
        $job->setJob($syncjob = new FakeJob(app(), json_encode([]), 'sync', 'sync'));

        app(Dispatcher::class)->dispatchNow($job);

        $this->assertInstanceOf(HttpClientException::class, $syncjob->exception);

        $address->refresh();
        $this->assertNull($address->latitude);
        $this->assertNull($address->longitude);
    }

    /** @test */
    public function it_fails_if_address_is_unknown(): void
    {
        $body = file_get_contents(base_path('tests/Fixtures/Services/Address/GetGPSCoordinateGarbageResponse.json'));
        Http::fake([
            'fake.com/v1/*' => Http::response($body, 404),
        ]);

        $address = Address::factory()->create([
            'line_1' => '',
            'city' => 'sieklopekznqqq',
            'postal_code' => '',
            'latitude' => null,
            'longitude' => null,
        ]);

        $request = [
            'address_id' => $address->id,
        ];

        $job = (new GetGPSCoordinate($request));
        $job->setJob($syncjob = new FakeJob(app(), json_encode([]), 'sync', 'sync'));

        app(Dispatcher::class)->dispatchNow($job);

        $this->assertInstanceOf(HttpClientException::class, $syncjob->exception);

        $address->refresh();
        $this->assertNull($address->latitude);
        $this->assertNull($address->longitude);
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

class FakeJob extends \Illuminate\Queue\Jobs\SyncJob
{
    public $exception;

    protected function failed($e)
    {
        $this->exception = $e;
    }
}
