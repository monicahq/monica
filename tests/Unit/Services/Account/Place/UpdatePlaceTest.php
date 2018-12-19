<?php

namespace Tests\Unit\Services\Account\Place;

use Tests\TestCase;
use App\Models\Account\Place;
use App\Models\Account\Account;
use App\Services\Account\Place\UpdatePlace;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdatePlaceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_a_place()
    {
        $place = factory(Place::class)->create([]);

        $request = [
            'account_id' => $place->account->id,
            'place_id' => $place->id,
            'street' => '199 Lafayette Street',
            'city' => 'New York City',
            'province' => '',
            'postal_code' => '',
            'country' => 'USA',
            'latitude' => '',
            'longitude' => '',
        ];

        $placeService = new UpdatePlace;
        $place = $placeService->execute($request);

        $this->assertDatabaseHas('places', [
            'id' => $place->id,
            'account_id' => $place->account_id,
            'latitude' => null,
            'city' => 'New York City',
        ]);

        $this->assertInstanceOf(
            Place::class,
            $place
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $place = factory(Place::class)->create([]);

        $request = [
            'street' => '199 Lafayette Street',
        ];

        $this->expectException(MissingParameterException::class);
        (new UpdatePlace)->execute($request);
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
        (new UpdatePlace)->execute($request);
    }
}
