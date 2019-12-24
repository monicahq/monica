<?php

namespace Tests\Unit\Services\Family\Family;

use Tests\TestCase;
use App\Models\Contact\Tag;
use App\Models\Account\Account;
use App\Models\Family\Family;
use App\Services\Family\Family\UpdateFamily;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateFamilyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_a_family()
    {
        $family = factory(Family::class)->create([]);

        $request = [
            'account_id' => $family->account_id,
            'family_id' => $family->id,
            'name' => 'Central Perk',
        ];

        $family = app(UpdateFamily::class)->execute($request);

        $this->assertDatabaseHas('families', [
            'id' => $family->id,
            'name' => 'Central Perk',
        ]);

        $this->assertInstanceOf(
            Family::class,
            $family
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
            'family_id' => 2,
        ];

        $this->expectException(ValidationException::class);

        app(UpdateFamily::class)->execute($request);
    }
}
