<?php

namespace Tests\Unit\Services\Account\Place;

use Tests\TestCase;
use App\Models\Account\Place;
use App\Models\Account\Account;
use App\Services\Account\Place\CreatePlace;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreatePlaceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_stores_a_place()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'street' => '199 Lafayette Street',
            'city' => 'New York City',
            'province' => '',
            'postal_code' => '',
            'country' => 'USA',
            'latitude' => '',
            'longitude' => '',
        ];

        $placeService = new CreatePlace;
        $place = $placeService->execute($request);

        $this->assertDatabaseHas('places', [
            'id' => $place->id,
            'account_id' => $account->id,
            'street' => '199 Lafayette Street',
            'latitude' => null,
        ]);

        $this->assertInstanceOf(
            Place::class,
            $place
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'street' => '199 Lafayette Street',
        ];

        $this->expectException(MissingParameterException::class);
        (new CreatePlace)->execute($request);
    }
}
