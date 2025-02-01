<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Exceptions\CantBeDeletedException;
use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class DestroyLifeEventCategory extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'life_event_category_id' => 'required|integer|exists:life_event_categories,id',
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
        ];
    }

    /**
     * Destroy a life event category.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $category = $this->vault->lifeEventCategories()
            ->findOrFail($data['life_event_category_id']);

        if (! $category->can_be_deleted) {
            throw new CantBeDeletedException;
        }

        $category->delete();
    }
}
