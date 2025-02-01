<?php

namespace Tests\Unit\Domains\Contact\ManageContactAddresses\Services;

use App\Domains\Contact\ManageContactAddresses\Services\RemoveAddressFromContact;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Address;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RemoveAddressFromContactTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_removes_a_contact_from_an_address(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $address = Address::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $address);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new RemoveAddressFromContact)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $address = Address::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $account, $vault, $contact, $address);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $address = Address::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $address);
    }

    /** @test */
    public function it_fails_if_contact_is_not_part_of_the_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $address = Address::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $address);
    }

    /** @test */
    public function it_fails_if_address_does_not_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $address = Address::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $contact, $address);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, Address $address): void
    {
        Queue::fake();

        $contact->addresses()->attach($address);

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'address_id' => $address->id,
        ];

        $address = (new RemoveAddressFromContact)->execute($request);

        $this->assertDatabaseMissing('contact_address', [
            'contact_id' => $contact->id,
            'address_id' => $address->id,
        ]);

        $this->assertInstanceOf(
            Address::class,
            $address
        );

        $this->assertDatabaseHas('contact_feed_items', [
            'contact_id' => $contact->id,
            'action' => ContactFeedItem::ACTION_CONTACT_ADDRESS_DESTROYED,
        ]);
    }
}
