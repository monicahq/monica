<?php

namespace Tests\Unit\Domains\Contact\ManageGroups\Services;

use App\Domains\Contact\ManageGroups\Services\RemoveContactFromGroup;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RemoveContactFromGroupTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_removes_a_contact_from_a_group(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $group->contacts()->attach($contact->id);

        $this->executeService($regis, $regis->account, $vault, $contact, $group);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new RemoveContactFromGroup)->execute($request);
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
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $group->contacts()->attach($contact->id);

        $this->executeService($regis, $account, $vault, $contact, $group);
    }

    /** @test */
    public function it_fails_if_label_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $group = Group::factory()->create();
        $group->contacts()->attach($contact->id);

        $this->executeService($regis, $regis->account, $vault, $contact, $group);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $group->contacts()->attach($contact->id);

        $this->executeService($regis, $regis->account, $vault, $contact, $group);
    }

    /** @test */
    public function it_fails_if_group_type_role_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $group = Group::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $group);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $group->contacts()->attach($contact->id);

        $this->executeService($regis, $regis->account, $vault, $contact, $group);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, Group $group): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'group_id' => $group->id,
            'contact_id' => $contact->id,
        ];

        (new RemoveContactFromGroup)->execute($request);

        $this->assertDatabaseMissing('contact_group', [
            'contact_id' => $contact->id,
            'group_id' => $group->id,
        ]);
    }
}
