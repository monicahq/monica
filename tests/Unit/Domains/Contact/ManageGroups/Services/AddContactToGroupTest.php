<?php

namespace Tests\Unit\Domains\Contact\ManageGroups\Services;

use App\Domains\Contact\ManageGroups\Services\AddContactToGroup;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupType;
use App\Models\GroupTypeRole;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AddContactToGroupTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_adds_a_contact_to_a_group(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $groupType = GroupType::factory()->create(['account_id' => $vault->account_id]);
        $group = Group::factory()->create([
            'group_type_id' => $groupType->id,
            'vault_id' => $vault->id,
        ]);
        $groupTypeRole = GroupTypeRole::factory()->create(['group_type_id' => $group->group_type_id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $group, $groupTypeRole);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new AddContactToGroup)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $groupType = GroupType::factory()->create(['account_id' => $vault->account_id]);
        $group = Group::factory()->create([
            'group_type_id' => $groupType->id,
            'vault_id' => $vault->id,
        ]);
        $groupTypeRole = GroupTypeRole::factory()->create(['group_type_id' => $group->group_type_id]);

        $this->executeService($regis, $account, $vault, $contact, $group, $groupTypeRole);
    }

    /** @test */
    public function it_fails_if_group_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $groupType = GroupType::factory()->create(['account_id' => $vault->account_id]);
        $group = Group::factory()->create([
            'group_type_id' => $groupType->id,
        ]);
        $groupTypeRole = GroupTypeRole::factory()->create(['group_type_id' => $group->group_type_id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $group, $groupTypeRole);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $groupType = GroupType::factory()->create(['account_id' => $vault->account_id]);
        $group = Group::factory()->create([
            'group_type_id' => $groupType->id,
            'vault_id' => $vault->id,
        ]);
        $groupTypeRole = GroupTypeRole::factory()->create(['group_type_id' => $group->group_type_id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $group, $groupTypeRole);
    }

    /** @test */
    public function it_fails_if_group_type_role_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $groupType = GroupType::factory()->create(['account_id' => $vault->account_id]);
        $group = Group::factory()->create([
            'group_type_id' => $groupType->id,
            'vault_id' => $vault->id,
        ]);
        $groupTypeRole = GroupTypeRole::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $contact, $group, $groupTypeRole);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $groupType = GroupType::factory()->create(['account_id' => $vault->account_id]);
        $group = Group::factory()->create([
            'group_type_id' => $groupType->id,
            'vault_id' => $vault->id,
        ]);
        $groupTypeRole = GroupTypeRole::factory()->create(['group_type_id' => $group->group_type_id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $group, $groupTypeRole);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, Group $group, GroupTypeRole $groupTypeRole): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'group_id' => $group->id,
            'group_type_role_id' => $groupTypeRole->id,
            'contact_id' => $contact->id,
        ];

        (new AddContactToGroup)->execute($request);

        $this->assertDatabaseHas('contact_group', [
            'contact_id' => $contact->id,
            'group_id' => $group->id,
            'group_type_role_id' => $groupTypeRole->id,
        ]);
    }
}
