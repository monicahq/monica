<?php

namespace Tests\Unit\Services\Account\Place;

use Tests\TestCase;
use App\Models\Account\Place;
use App\Models\Account\Account;
use App\Services\Account\Place\DestroyPlace;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyPlaceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_place()
    {
        $place = factory(Place::class)->create([]);

        $request = [
            'account_id' => $place->account->id,
            'place_id' => $place->id,
        ];

        $placeService = new DestroyPlace;
        $bool = $placeService->execute($request);

        $this->assertDatabaseMissing('places', [
            'id' => $place->id,
        ]);
    }

    public function test_it_throws_an_exception_if_account_is_not_linked_to_places()
    {
        $account = factory(Account::class)->create([]);
        $place = factory(Place::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'place_id' => $place->id,
        ];

        $this->expectException(ModelNotFoundException::class);
        (new DestroyPlace)->execute($request);
    }

    public function test_it_throws_an_exception_if_ids_do_not_exist()
    {
        $request = [
            'account_id' => 11111111,
            'place_id' => 11111111,
        ];

        $this->expectException(MissingParameterException::class);
        (new DestroyPlace)->execute($request);
    }
}
