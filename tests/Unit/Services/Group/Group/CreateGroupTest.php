<?php

namespace Tests\Unit\Services\Group\Group;

use Tests\TestCase;
use App\Models\Group\Group;
use App\Models\Account\Account;
use App\Services\Group\Group\CreateGroup;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreateGroupTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_stores_a_family()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'name' => 'family',
        ];

        $group = app(CreateGroup::class)->execute($request);

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'account_id' => $group->account_id,
            'name' => 'family',
        ]);

        $this->assertInstanceOf(
            Group::class,
            $group
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $account = factory(Account::class)->create([]);
        $request = [
            'account_id' => $account->id,
        ];

        $this->expectException(ValidationException::class);
        app(CreateGroup::class)->execute($request);
    }
}
