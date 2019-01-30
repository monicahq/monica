<?php

namespace Tests\Unit\Services\Account\Place;

use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use App\Models\Account\Place;
use GuzzleHttp\Psr7\Response;
use App\Models\Account\Account;
use GuzzleHttp\Handler\MockHandler;
use App\Services\Account\Place\UpdatePlace;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdatePlaceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_a_place_without_fetching_geolocation_information()
    {
        $place = factory(Place::class)->create([]);

        $request = [
            'account_id' => $place->account_id,
            'place_id' => $place->id,
            'street' => '199 Lafayette Street',
            'city' => 'New York City',
            'province' => '',
            'postal_code' => '',
            'country' => 'USA',
            'latitude' => '10',
            'longitude' => '10',
        ];

        $place = app(UpdatePlace::class)->execute($request);

        $this->assertDatabaseHas('places', [
            'id' => $place->id,
            'account_id' => $place->account_id,
            'latitude' => 10,
            'city' => 'New York City',
        ]);

        $this->assertInstanceOf(
            Place::class,
            $place
        );
    }

    public function test_it_updates_a_place_and_fetch_geolocation_information()
    {
        config(['monica.enable_geolocation' => true]);
        config(['monica.location_iq_api_key' => 'test']);

        $body = file_get_contents(base_path('tests/Fixtures/Services/Account/Place/UpdatePlaceSampleResponse.json'));
        $mock = new MockHandler([new Response(200, [], $body)]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $place = factory(Place::class)->create([]);

        $request = [
            'account_id' => $place->account_id,
            'place_id' => $place->id,
            'street' => '12',
            'city' => 'beverly hills',
            'province' => '',
            'postal_code' => '90210',
            'country' => 'US',
            'latitude' => '',
            'longitude' => '',
        ];

        $place = app(UpdatePlace::class)->execute($request, $client);

        $this->assertDatabaseHas('places', [
            'id' => $place->id,
            'account_id' => $place->account_id,
            'street' => '12',
            'latitude' => 34.0736204,
            'longitude' => -118.4003563,
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $place = factory(Place::class)->create([]);

        $request = [
            'street' => '199 Lafayette Street',
        ];

        $this->expectException(ValidationException::class);
        app(UpdatePlace::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_place_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create([]);
        $place = factory(Place::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'place_id' => $place->id,
        ];

        $this->expectException(ModelNotFoundException::class);
        app(UpdatePlace::class)->execute($request);
    }
}
