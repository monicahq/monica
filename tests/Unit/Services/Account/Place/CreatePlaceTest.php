<?php

namespace Tests\Unit\Services\Account\Place;

use Tests\TestCase;
use App\Models\Account\Place;
use App\Models\Account\Account;
use Illuminate\Support\Facades\Http;
use App\Services\Account\Place\CreatePlace;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreatePlaceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_stores_a_place_without_fetching_geolocation_information()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'street' => '199 Lafayette Street',
            'city' => 'New York City',
            'province' => '',
            'postal_code' => '',
            'country' => 'USA',
            'latitude' => '10',
            'longitude' => '10',
        ];

        $place = app(CreatePlace::class)->execute($request);

        $this->assertDatabaseHas('places', [
            'id' => $place->id,
            'account_id' => $account->id,
            'street' => '199 Lafayette Street',
            'latitude' => 10,
        ]);

        $this->assertInstanceOf(
            Place::class,
            $place
        );
    }

    /** @test */
    public function it_stores_a_place_and_fetch_geolocation_information()
    {
        config(['monica.enable_geolocation' => true]);
        config(['monica.location_iq_api_key' => 'test']);

        $body = file_get_contents(base_path('tests/Fixtures/Services/Account/Place/CreatePlaceSampleResponse.json'));
        Http::fake([
            'us1.locationiq.com/v1/*' => Http::response($body, 200),
        ]);

        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'street' => '12',
            'city' => 'beverly hills',
            'province' => '',
            'postal_code' => '90210',
            'country' => 'US',
            'latitude' => '',
            'longitude' => '',
        ];

        $place = app(CreatePlace::class)->execute($request);

        $this->assertDatabaseHas('places', [
            'id' => $place->id,
            'account_id' => $account->id,
            'street' => '12',
            'latitude' => 34.0736204,
            'longitude' => -118.4003563,
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        factory(Account::class)->create([]);

        $request = [
            'street' => '199 Lafayette Street',
        ];

        $this->expectException(ValidationException::class);
        app(CreatePlace::class)->execute($request);
    }
}
