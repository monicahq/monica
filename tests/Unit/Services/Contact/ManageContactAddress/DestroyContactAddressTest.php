<?php

namespace Tests\Unit\Services\Contact\ManageContactAddress;

use Tests\TestCase;
use App\Models\User;
use App\Models\Place;
use App\Models\Vault;
use App\Models\Account;
use App\Models\Contact;
use App\Models\AddressType;
use App\Jobs\CreateAuditLog;
use App\Jobs\CreateContactLog;
use App\Models\ContactAddress;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use App\Exceptions\NotEnoughPermissionException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\ManageContactAddress\DestroyContactAddress;

class DestroyContactAddressTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_contact_information(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $type = AddressType::factory()->create(['account_id' => $regis->account_id]);
        $address = ContactAddress::factory()->create([
            'address_type_id' => $type->id,
            'contact_id' => $contact->id,
        ]);
        Place::factory()->create([
            'placeable_id' => $address->id,
            'placeable_type' => 'App\Models\ContactAddress',
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $type, $address);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyContactAddress)->execute($request);
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
        $type = AddressType::factory()->create(['account_id' => $regis->account_id]);
        $address = ContactAddress::factory()->create([
            'address_type_id' => $type->id,
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $account, $vault, $contact, $type, $address);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $type = AddressType::factory()->create(['account_id' => $regis->account_id]);
        $address = ContactAddress::factory()->create([
            'address_type_id' => $type->id,
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $type, $address);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $type = AddressType::factory()->create(['account_id' => $regis->account_id]);
        $address = ContactAddress::factory()->create([
            'address_type_id' => $type->id,
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $type, $address);
    }

    /** @test */
    public function it_fails_if_type_is_not_in_the_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $type = AddressType::factory()->create();
        $address = ContactAddress::factory()->create([
            'address_type_id' => $type->id,
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $type, $address);
    }

    /** @test */
    public function it_fails_if_information_does_not_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $type = AddressType::factory()->create();
        $address = ContactAddress::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $contact, $type, $address);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, AddressType $type, ContactAddress $address): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'address_type_id' => $type->id,
            'contact_address_id' => $address->id,
        ];

        (new DestroyContactAddress)->execute($request);

        $this->assertDatabaseMissing('contact_addresses', [
            'id' => $address->id,
        ]);

        Queue::assertPushed(CreateAuditLog::class, function ($job) {
            return $job->auditLog['action_name'] === 'contact_address_destroyed';
        });

        Queue::assertPushed(CreateContactLog::class, function ($job) {
            return $job->contactLog['action_name'] === 'contact_address_destroyed';
        });
    }
}
