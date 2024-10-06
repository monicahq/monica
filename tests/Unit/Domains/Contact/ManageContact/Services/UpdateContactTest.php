<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Services;

use App\Domains\Contact\ManageContact\Services\UpdateContact;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Gender;
use App\Models\Pronoun;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateContactTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_contact(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $this->executeService($regis, $regis->account, $vault, $contact);
    }

    /** @test */
    public function it_updates_a_contact_with_pronoun(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $pronoun = Pronoun::factory()->create([
            'account_id' => $regis->account_id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, pronoun: $pronoun);
    }

    /** @test */
    public function it_updates_a_contact_with_gender(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $gender = Gender::factory()->create([
            'account_id' => $regis->account_id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, gender: $gender);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateContact)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = $this->createAccount();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $account, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $otherVault = Vault::factory()->create();

        $this->executeService($regis, $regis->account, $otherVault, $contact);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $regis->account, $vault, $contact);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, ?Gender $gender = null, ?Pronoun $pronoun = null): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'first_name' => 'Ross',
            'gender_id' => optional($gender)->id,
            'pronoun_id' => optional($pronoun)->id,
        ];

        $contact = (new UpdateContact)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'vault_id' => $vault->id,
            'first_name' => 'Ross',
            'gender_id' => optional($gender)->id,
            'pronoun_id' => optional($pronoun)->id,
        ]);
    }
}
