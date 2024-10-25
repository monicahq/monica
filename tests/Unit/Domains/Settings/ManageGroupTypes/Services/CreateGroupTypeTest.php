<?php

namespace Tests\Unit\Domains\Settings\ManageGroupTypes\Services;

use App\Domains\Settings\ManageGroupTypes\Services\CreateGroupType;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\GroupType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateGroupTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_group_type(): void
    {
        $ross = $this->createAdministrator();
        $this->executeService($ross, $ross->account);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateGroupType)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->executeService($ross, $account);
    }

    /** @test */
    public function it_fails_if_user_is_not_administrator(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $this->executeService($ross, $ross->account);
    }

    private function executeService(User $author, Account $account): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'label' => 'type name',
        ];

        $groupType = (new CreateGroupType)->execute($request);

        $this->assertDatabaseHas('group_types', [
            'id' => $groupType->id,
            'account_id' => $account->id,
            'label' => 'type name',
            'position' => 1,
        ]);

        $this->assertInstanceOf(
            GroupType::class,
            $groupType
        );
    }
}
