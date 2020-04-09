<?php

namespace Tests\Unit\Services\Account\Place;

use Tests\TestCase;
use App\Models\Account\Place;
use App\Models\Account\Account;
use App\Services\Account\Place\DestroyPlace;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyPlaceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_place()
    {
        $place = factory(Place::class)->create([]);

        $request = [
            'account_id' => $place->account_id,
            'place_id' => $place->id,
        ];

        app(DestroyPlace::class)->execute($request);

        $this->assertDatabaseMissing('places', [
            'id' => $place->id,
        ]);
    }

    /** @test */
    public function it_throws_an_exception_if_account_is_not_linked_to_places()
    {
        $account = factory(Account::class)->create([]);
        $place = factory(Place::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'place_id' => $place->id,
        ];

        $this->expectException(ModelNotFoundException::class);
        app(DestroyPlace::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_ids_do_not_exist()
    {
        $request = [
            'account_id' => 11111111,
            'place_id' => 11111111,
        ];

        $this->expectException(ValidationException::class);
        app(DestroyPlace::class)->execute($request);
    }
}
