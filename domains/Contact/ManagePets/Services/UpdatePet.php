<?php

namespace App\Contact\ManagePets\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\Pet;
use App\Models\PetCategory;
use App\Services\BaseService;
use Carbon\Carbon;

class UpdatePet extends BaseService implements ServiceInterface
{
    private Pet $pet;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'vault_id' => 'required|integer|exists:vaults,id',
            'author_id' => 'required|integer|exists:users,id',
            'contact_id' => 'required|integer|exists:contacts,id',
            'pet_id' => 'required|integer|exists:pets,id',
            'pet_category_id' => 'required|integer|exists:pet_categories,id',
            'name' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Update a pet.
     *
     * @param  array  $data
     * @return Pet
     */
    public function execute(array $data): Pet
    {
        $this->validateRules($data);

        $petCategory = PetCategory::where('account_id', $data['account_id'])
            ->findOrFail($data['pet_category_id']);

        $this->pet = Pet::where('contact_id', $data['contact_id'])
            ->where('pet_category_id', $petCategory->id)
            ->findOrFail($data['pet_id']);

        $this->pet->pet_category_id = $data['pet_category_id'];
        $this->pet->name = $this->valueOrNull($data, 'name');
        $this->pet->save();

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
            'action' => ContactFeedItem::ACTION_PET_UPDATED,
            'description' => $this->pet->petCategory->name,
        ]);

        $this->pet->feedItem()->save($feedItem);
    }
}
