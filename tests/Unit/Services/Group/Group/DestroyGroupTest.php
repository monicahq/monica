<?php

namespace Tests\Unit\Services\Group\Group;

use Tests\TestCase;
use App\Models\Group\Group;
use App\Services\Group\Group\DestroyGroup;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DestroyGroupTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_family()
    {
        $group = factory(Group::class)->create([]);

        $request = [
            'account_id' => $group->account_id,
            'group_id' => $group->id,
        ];

        app(DestroyGroup::class)->execute($request);

        $this->assertDatabaseMissing('groups', [
            'id' => $group->id,
            'account_id' => $group->account_id,
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
        ];

        $this->expectException(ValidationException::class);

        app(DestroyGroup::class)->execute($request);
    }
}
