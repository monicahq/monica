<?php

namespace Tests\Unit\Domains\Vault\ManageVault\Services;

use App\Domains\Vault\ManageVault\Services\CreateVault;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateVaultTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_vault(): void
    {
        $ross = $this->createUser();
        $this->executeService($ross, $ross->account);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateVault)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createUser();
        $account = $this->createAccount();
        $this->executeService($ross, $account);
    }

    private function executeService(User $author, Account $account): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'name' => 'vault name',
            'type' => Vault::TYPE_PERSONAL,
        ];

        $vault = (new CreateVault)->execute($request);

        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'account_id' => $account->id,
            'name' => 'vault name',
            'type' => Vault::TYPE_PERSONAL,
        ]);

        $this->assertDatabaseCount('contacts', 1);

        $contact = Contact::first();

        $this->assertFalse(
            $contact->can_be_deleted
        );

        $this->assertDatabaseHas('user_vault', [
            'vault_id' => $vault->id,
            'user_id' => $author->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertDatabaseHas('contact_important_date_types', [
            'vault_id' => $vault->id,
            'label' => 'Birthdate',
            'internal_type' => ContactImportantDate::TYPE_BIRTHDATE,
            'can_be_deleted' => false,
        ]);
        $this->assertDatabaseHas('contact_important_date_types', [
            'vault_id' => $vault->id,
            'label' => 'Deceased date',
            'internal_type' => ContactImportantDate::TYPE_DECEASED_DATE,
            'can_be_deleted' => false,
        ]);

        $this->assertInstanceOf(
            Vault::class,
            $vault
        );
    }
}
