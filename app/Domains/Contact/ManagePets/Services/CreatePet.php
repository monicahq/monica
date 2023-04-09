<?php

namespace App\Domains\Contact\ManagePets\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\Pet;
use App\Services\BaseService;
use Carbon\Carbon;

class CreatePet extends BaseService implements ServiceInterface
{
    private Pet $pet;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'contact_id' => 'required|uuid|exists:contacts,id',
            'pet_category_id' => 'required|integer|exists:pet_categories,id',
            'name' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_vault_editor',
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Create a pet.
     */
    public function execute(array $data): Pet
    {
        $this->validateRules($data);

        $this->account()->petCategories()
            ->findOrFail($data['pet_category_id']);

        $this->pet = Pet::create([
            'contact_id' => $this->contact->id,
            'pet_category_id' => $data['pet_category_id'],
            'name' => $this->valueOrNull($data, 'name'),
        ]);

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        $this->createFeedItem();

        return $this->pet;
    }

    private function createFeedItem(): void
    {
        $feedItem = ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_PET_CREATED,
            'description' => $this->pet->petCategory->name,
        ]);

        $this->pet->feedItem()->save($feedItem);
    }
}
