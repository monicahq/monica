<?php

namespace App\Settings\ManagePetCategories\Services;

use App\Interfaces\ServiceInterface;
use App\Models\PetCategory;
use App\Services\BaseService;

class DestroyPetCategory extends BaseService implements ServiceInterface
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
     * Destroy a pet category.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $petCategory = PetCategory::where('account_id', $data['account_id'])
            ->findOrFail($data['pet_category_id']);

        $petCategory->delete();
    }
}
