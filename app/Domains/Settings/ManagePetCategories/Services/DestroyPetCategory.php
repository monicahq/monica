<?php

namespace App\Domains\Settings\ManagePetCategories\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class DestroyPetCategory extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'pet_category_id' => 'required|integer|exists:pet_categories,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
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
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $petCategory = $this->account()->petCategories()
            ->findOrFail($data['pet_category_id']);

        $petCategory->delete();
    }
}
