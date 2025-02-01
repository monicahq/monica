<?php

namespace Tests\Unit\Domains\Contact\ManageReligion\Services;

use App\Domains\Contact\ManageReligion\Services\UpdateReligion;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Religion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateReligionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_the_religion(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $religion = Religion::factory()->create(['account_id' => $regis->account_id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $religion);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateReligion)->execute($request);
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
        $religion = Religion::factory()->create(['account_id' => $regis->account_id]);

        $this->executeService($regis, $account, $vault, $contact, $religion);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $religion = Religion::factory()->create(['account_id' => $regis->account_id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $religion);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $religion = Religion::factory()->create(['account_id' => $regis->account_id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $religion);
    }

    /** @test */
    public function it_fails_if_religion_is_not_in_the_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $religion = Religion::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $contact, $religion);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, Religion $religion): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'religion_id' => $religion->id,
        ];

        $contact = (new UpdateReligion)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'religion_id' => $religion->id,
        ]);
    }
}
