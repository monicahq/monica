<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Services;

use App\Domains\Contact\ManageContact\Services\UpdateContactSortOrder;
use App\Models\Account;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateContactSortOrderTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_the_way_contact_are_sorted(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $this->executeService($regis, $regis->account, $vault);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateContactSortOrder)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = $this->createAccount();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);

        $this->executeService($regis, $account, $vault);
    }

    private function executeService(User $author, Account $account, Vault $vault): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'sort_order' => User::CONTACT_SORT_ORDER_DESC,
        ];

        (new UpdateContactSortOrder)->execute($request);

        $this->assertDatabaseHas('users', [
            'id' => $author->id,
            'contact_sort_order' => User::CONTACT_SORT_ORDER_DESC,
        ]);
    }
}
