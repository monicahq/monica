<?php

namespace Tests\Unit\Domains\Contact\ManageContactImportantDates\Services;

use App\Domains\Contact\ManageContactImportantDates\Services\UpdateContactImportantDate;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\ContactImportantDateType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateContactImportantDateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_contact_date(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $date = ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $date);
    }

    /** @test */
    public function it_updates_a_contact_date_with_type(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $date = ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
        ]);
        $contactImportantDateType = ContactImportantDateType::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $date, $contactImportantDateType);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateContactImportantDate)->execute($request);
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
        $date = ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $account, $vault, $contact, $date);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $date = ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $date);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $date = ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $date);
    }

    /** @test */
    public function it_fails_if_date_does_not_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $date = ContactImportantDate::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $contact, $date);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, ContactImportantDate $date, ?ContactImportantDateType $contactImportantDateType = null): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'contact_important_date_id' => $date->id,
            'label' => 'birthdate',
            'day' => 29,
            'month' => 10,
            'year' => 1981,
        ];

        if ($contactImportantDateType !== null) {
            $request['contact_important_date_type_id'] = $contactImportantDateType->id;
        }

        $date = (new UpdateContactImportantDate)->execute($request);

        $this->assertDatabaseHas('contact_important_dates', [
            'contact_id' => $contact->id,
            'label' => 'birthdate',
            'day' => 29,
            'month' => 10,
            'year' => 1981,
            'contact_important_date_type_id' => optional($contactImportantDateType)->id,
        ]);

        $this->assertInstanceOf(
            ContactImportantDate::class,
            $date
        );
    }
}
