<?php

namespace Tests\Unit\Domains\Settings\ManageUsers\Services;

use App\Domains\Settings\ManageUsers\Services\GiveAdministratorPrivilege;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GiveAdministratorPrivilegeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gives_the_administrator_privilege_to_another_user(): void
    {
        $author = $this->createAdministrator();
        $anotherUser = User::factory()->create([
            'account_id' => $author->account_id,
        ]);
        $this->executeService($author->account, $author, $anotherUser);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $author = $this->createAdministrator();
        $account = Account::factory()->create();
        $anotherUser = User::factory()->create([
            'account_id' => $author->account_id,
        ]);
        $this->expectException(ModelNotFoundException::class);
        $this->executeService($account, $author, $anotherUser);
    }

    /** @test */
    public function it_fails_if_the_other_user_doesnt_belong_to_account(): void
    {
        $author = $this->createAdministrator();
        $anotherUser = User::factory()->create();
        $this->expectException(ModelNotFoundException::class);
        $this->executeService($author->account, $author, $anotherUser);
    }

    /** @test */
    public function it_fails_if_user_is_not_account_administrator(): void
    {
        $author = $this->createUser();
        $anotherUser = User::factory()->create([
            'account_id' => $author->account_id,
        ]);

        $this->expectException(NotEnoughPermissionException::class);
        $this->executeService($author->account, $author, $anotherUser);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new GiveAdministratorPrivilege)->execute($request);
    }

    private function executeService(Account $account, User $author, User $anotherUser): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'user_id' => $anotherUser->id,
        ];

        (new GiveAdministratorPrivilege)->execute($request);

        $this->assertDatabaseHas('users', [
            'id' => $anotherUser->id,
            'is_account_administrator' => true,
        ]);
    }
}
