<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Interfaces\ServiceInterface;
use App\Models\LifeEventCategory;
use App\Services\BaseService;

class CreateLifeEventCategory extends BaseService implements ServiceInterface
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
            'label' => 'required|string|max:255',
            'can_be_deleted' => 'required|boolean',
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
     * Create a life event category.
     */
    public function execute(array $data): LifeEventCategory
    {
        $this->validateRules($data);

        // determine the new position of the life event type
        $newPosition = $this->vault->lifeEventCategories()
            ->max('position');
        $newPosition++;

        return LifeEventCategory::create([
            'vault_id' => $data['vault_id'],
            'label' => $data['label'],
            'can_be_deleted' => $data['can_be_deleted'],
            'position' => $newPosition,
        ]);
    }
}
