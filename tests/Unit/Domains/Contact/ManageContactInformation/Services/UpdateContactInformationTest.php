<?php

namespace Tests\Unit\Domains\Contact\ManageContactInformation\Services;

use App\Domains\Contact\ManageContactInformation\Services\UpdateContactInformation;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\ContactInformation;
use App\Models\ContactInformationType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateContactInformationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_contact_information(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $type = ContactInformationType::factory()->create(['account_id' => $regis->account_id]);
        $information = ContactInformation::factory()->create([
            'type_id' => $type->id,
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $type, $information);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateContactInformation)->execute($request);
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
        $type = ContactInformationType::factory()->create(['account_id' => $regis->account_id]);
        $information = ContactInformation::factory()->create([
            'type_id' => $type->id,
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $account, $vault, $contact, $type, $information);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $type = ContactInformationType::factory()->create(['account_id' => $regis->account_id]);
        $information = ContactInformation::factory()->create([
            'type_id' => $type->id,
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $type, $information);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $type = ContactInformationType::factory()->create(['account_id' => $regis->account_id]);
        $information = ContactInformation::factory()->create([
            'type_id' => $type->id,
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $type, $information);
    }

    /** @test */
    public function it_fails_if_type_is_not_in_the_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $type = ContactInformationType::factory()->create();
        $information = ContactInformation::factory()->create([
            'type_id' => $type->id,
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $type, $information);
    }

    /** @test */
    public function it_fails_if_information_does_not_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $type = ContactInformationType::factory()->create();
        $information = ContactInformation::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $contact, $type, $information);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, ContactInformationType $type, ContactInformation $information): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'contact_information_type_id' => $type->id,
            'contact_information_id' => $information->id,
            'data' => '45322322',
        ];

        $contactInfo = (new UpdateContactInformation)->execute($request);

        $this->assertDatabaseHas('contact_information', [
            'id' => $contactInfo->id,
            'data' => 45322322,
        ]);

        $this->assertDatabaseHas('contact_feed_items', [
            'contact_id' => $contact->id,
            'action' => ContactFeedItem::ACTION_CONTACT_INFORMATION_UPDATED,
        ]);
    }
}
