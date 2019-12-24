<?php

namespace Tests\Unit\Services\Family\Family;

use Tests\TestCase;
use App\Models\Family\Family;
use App\Services\Family\Family\DestroyFamily;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DestroyFamilyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_tag()
    {
        $family = factory(Family::class)->create([]);

        $request = [
            'account_id' => $family->account_id,
            'family_id' => $family->id,
        ];

        app(DestroyFamily::class)->execute($request);

        $this->assertDatabaseMissing('families', [
            'id' => $family->id,
            'account_id' => $family->account_id,
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
        ];

        $this->expectException(ValidationException::class);

        app(DestroyFamily::class)->execute($request);
    }
}
