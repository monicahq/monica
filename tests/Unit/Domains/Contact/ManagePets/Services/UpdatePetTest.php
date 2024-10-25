<?php

namespace Tests\Unit\Domains\Contact\ManagePets\Services;

use App\Domains\Contact\ManagePets\Services\UpdatePet;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\ContactInformationType;
use App\Models\Pet;
use App\Models\PetCategory;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdatePetTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_pet(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $petCategory = PetCategory::factory()->create(['account_id' => $regis->account_id]);
        $pet = Pet::factory()->create([
            'contact_id' => $contact->id,
            'pet_category_id' => $petCategory->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $pet, $petCategory);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdatePet)->execute($request);
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
        $petCategory = PetCategory::factory()->create(['account_id' => $regis->account_id]);
        $pet = Pet::factory()->create([
            'contact_id' => $contact->id,
            'pet_category_id' => $petCategory->id,
        ]);

        $this->executeService($regis, $account, $vault, $contact, $pet, $petCategory);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $petCategory = PetCategory::factory()->create(['account_id' => $regis->account_id]);
        $pet = Pet::factory()->create([
            'contact_id' => $contact->id,
            'pet_category_id' => $petCategory->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $pet, $petCategory);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $petCategory = PetCategory::factory()->create(['account_id' => $regis->account_id]);
        $pet = Pet::factory()->create([
            'contact_id' => $contact->id,
            'pet_category_id' => $petCategory->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $pet, $petCategory);
    }

    /** @test */
    public function it_fails_if_pet_is_not_in_the_contact(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $pet = ContactInformationType::factory()->create();
        $petCategory = PetCategory::factory()->create(['account_id' => $regis->account_id]);
        $pet = Pet::factory()->create([
            'pet_category_id' => $petCategory->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $pet, $petCategory);
    }

    /** @test */
    public function it_fails_if_pet_category_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $petCategory = PetCategory::factory()->create();
        $pet = Pet::factory()->create([
            'contact_id' => $contact->id,
            'pet_category_id' => $petCategory->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $pet, $petCategory);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, Pet $pet, PetCategory $petCategory): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'pet_id' => $pet->id,
            'pet_category_id' => $petCategory->id,
            'name' => 'boubou',
        ];

        $pet = (new UpdatePet)->execute($request);

        $this->assertDatabaseHas('pets', [
            'id' => $pet->id,
            'contact_id' => $contact->id,
            'pet_category_id' => $petCategory->id,
            'name' => 'boubou',
        ]);

        $this->assertDatabaseHas('contact_feed_items', [
            'contact_id' => $contact->id,
            'action' => ContactFeedItem::ACTION_PET_UPDATED,
        ]);
    }
}
