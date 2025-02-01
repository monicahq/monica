<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Services;

use App\Domains\Contact\ManageContact\Services\MoveContactToAnotherVault;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MoveContactToAnotherVaultTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_moves_a_contact_to_another_vault(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $newVault = $this->createVault($regis->account);
        $newVault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $newVault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $regis->account, $vault, $newVault, $contact);
    }

    /** @test */
    public function it_moves_a_contact_to_another_vault_and_copy_the_company_information_if_there_are_multiple_contacts_in_it(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $newVault = $this->createVault($regis->account);
        $newVault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $newVault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $company = Company::factory()->create(['vault_id' => $vault->id]);
        Contact::factory()->count(2)->create(['vault_id' => $vault->id, 'company_id' => $company->id]);
        $contact->company_id = $company->id;
        $contact->save();

        $this->executeService($regis, $regis->account, $vault, $newVault, $contact);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
        ]);

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
            'company_id' => $company->id,
        ]);
    }

    /** @test */
    public function it_moves_a_contact_to_another_vault_and_move_the_company_information_if_there_are_no_other_contacts_in_it(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $newVault = $this->createVault($regis->account);
        $newVault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $newVault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $company = Company::factory()->create(['vault_id' => $vault->id]);
        $contact->company_id = $company->id;
        $contact->save();

        $this->executeService($regis, $regis->account, $vault, $newVault, $contact);

        $this->assertDatabaseMissing('companies', [
            'id' => $company->id,
            'vault_id' => $vault->id,
        ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'vault_id' => $newVault->id,
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new MoveContactToAnotherVault)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = $this->createAccount();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $newVault = $this->createVault($regis->account);
        $newVault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $newVault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $account, $vault, $newVault, $contact);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $otherVault = Vault::factory()->create();
        $newVault = $this->createVault($regis->account);
        $newVault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $newVault);

        $this->executeService($regis, $regis->account, $otherVault, $newVault, $contact);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $newVault = $this->createVault($regis->account);
        $newVault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $newVault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $regis->account, $vault, $newVault, $contact);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_destination_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $newVault = $this->createVault($regis->account);
        $newVault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $newVault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $regis->account, $vault, $newVault, $contact);
    }

    private function executeService(User $author, Account $account, Vault $vault, Vault $newVault, Contact $contact): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'other_vault_id' => $newVault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
        ];

        $contact = (new MoveContactToAnotherVault)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'vault_id' => $newVault->id,
        ]);

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
            'vault_id' => $vault->id,
        ]);
    }
}
