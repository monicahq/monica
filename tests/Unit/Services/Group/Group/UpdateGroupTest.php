<?php

namespace Tests\Unit\Services\Group\Group;

use Tests\TestCase;
use App\Models\Group\Group;
use App\Services\Group\Group\UpdateGroup;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UpdateGroupTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_family()
    {
        $group = factory(Group::class)->create([]);

        $request = [
            'account_id' => $group->account_id,
            'group_id' => $group->id,
            'name' => 'Central Perk',
        ];

        $group = app(UpdateGroup::class)->execute($request);

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'name' => 'Central Perk',
        ]);

        $this->assertInstanceOf(
            Group::class,
            $group
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
            'group_id' => 2,
        ];

        $this->expectException(ValidationException::class);

        app(UpdateGroup::class)->execute($request);
    }
}
