<?php

namespace App\Settings\ManagePetCategories\Services;

use App\Models\PetCategory;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class UpdatePetCategory extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'pet_category_id' => 'required|integer|exists:pet_categories,id',
            'name' => 'required|string|max:255',
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
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Update a pet category.
     *
     * @param  array  $data
     * @return PetCategory
     */
    public function execute(array $data): PetCategory
    {
        $this->validateRules($data);

        $petCategory = PetCategory::where('account_id', $data['account_id'])
            ->findOrFail($data['pet_category_id']);

        $petCategory->name = $data['name'];
        $petCategory->save();

        return $petCategory;
    }
}
