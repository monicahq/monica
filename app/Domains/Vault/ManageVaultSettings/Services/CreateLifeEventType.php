<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Interfaces\ServiceInterface;
use App\Models\LifeEventType;
use App\Services\BaseService;

class CreateLifeEventType extends BaseService implements ServiceInterface
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
     * Create a life event type.
     */
    public function execute(array $data): LifeEventType
    {
        $this->validateRules($data);

        $category = $this->vault->lifeEventCategories()
            ->findOrFail($data['life_event_category_id']);

        // determine the new position of the life event type
        $newPosition = $category->lifeEventTypes()
            ->max('position');
        $newPosition++;

        $type = LifeEventType::create([
            'life_event_category_id' => $category->id,
            'label' => $data['label'],
            'can_be_deleted' => $data['can_be_deleted'],
            'position' => $newPosition,
        ]);

        return $type;
    }
}
