<?php

namespace Tests\Unit\Domains\Contact\ManagePronouns\Services;

use App\Domains\Contact\ManagePronouns\Services\SetPronoun;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Pronoun;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SetPronounTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_sets_pronoun(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $pronoun = Pronoun::factory()->create(['account_id' => $regis->account_id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $pronoun);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new SetPronoun)->execute($request);
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
        $pronoun = Pronoun::factory()->create(['account_id' => $regis->account_id]);

        $this->executeService($regis, $account, $vault, $contact, $pronoun);
    }

    /** @test */
    public function it_fails_if_pronoun_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $pronoun = Pronoun::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $contact, $pronoun);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $pronoun = Pronoun::factory()->create(['account_id' => $regis->account_id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $pronoun);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $pronoun = Pronoun::factory()->create(['account_id' => $regis->account_id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $pronoun);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, Pronoun $pronoun): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'pronoun_id' => $pronoun->id,
        ];

        (new SetPronoun)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'pronoun_id' => $pronoun->id,
        ]);
    }
}
