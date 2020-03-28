<?php

namespace Tests\Unit\Services\Contact\Call;

use App\Models\Contact\Occupation;
use App\Services\Contact\Occupation\DestroyOccupation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DestroyOccupationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_occupation()
    {
        $occupation = factory(Occupation::class)->create([]);

        $request = [
            'account_id' => $occupation->account_id,
            'occupation_id' => $occupation->id,
        ];

        $this->assertDatabaseHas('occupations', [
            'id' => $occupation->id,
        ]);

        app(DestroyOccupation::class)->execute($request);

        $this->assertDatabaseMissing('occupations', [
            'id' => $occupation->id,
        ]);
    }
}
