<?php

namespace Tests\Unit\Domains\Settings\ManageUsers\Services;

use App\Domains\Settings\ManageUsers\Services\RemoveAdministratorPrivilege;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RemoveAdministratorPrivilegeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_removes_the_administrator_privilege_from_another_user(): void
    {
        $author = $this->createAdministrator();
        $anotherUser = User::factory()->create([
            'account_id' => $author->account_id,
            'is_account_administrator' => true,
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
            'is_account_administrator' => true,
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
            'is_account_administrator' => true,
        ]);

        $this->expectException(NotEnoughPermissionException::class);
        $this->executeService($author->account, $author, $anotherUser);
    }

    /** @test */
    public function it_fails_if_the_two_users_are_the_same(): void
    {
        $author = $this->createUser();
        $this->expectException(Exception::class);
        $this->executeService($author->account, $author, $author);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new RemoveAdministratorPrivilege)->execute($request);
    }

    private function executeService(Account $account, User $author, User $anotherUser): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'user_id' => $anotherUser->id,
        ];

        (new RemoveAdministratorPrivilege)->execute($request);

        $this->assertDatabaseHas('users', [
            'id' => $anotherUser->id,
            'is_account_administrator' => false,
        ]);
    }
}
