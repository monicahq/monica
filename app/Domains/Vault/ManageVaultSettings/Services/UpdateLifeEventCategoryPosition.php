<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Interfaces\ServiceInterface;
use App\Models\LifeEventCategory;
use App\Services\BaseService;

class UpdateLifeEventCategoryPosition extends BaseService implements ServiceInterface
{
    private LifeEventCategory $lifeEventCategory;

    private int $pastPosition;

    private array $data;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'life_event_category_id' => 'required|integer|exists:life_event_categories,id',
            'new_position' => 'required|integer',
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
     * Update the life event type's position.
     */
    public function execute(array $data): LifeEventCategory
    {
        $this->data = $data;
        $this->validate();
        $this->updatePosition();

        return $this->lifeEventCategory;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->lifeEventCategory = $this->vault->lifeEventCategories()
            ->findOrFail($this->data['life_event_category_id']);

        $this->pastPosition = $this->lifeEventCategory->position;
    }

    private function updatePosition(): void
    {
        if ($this->data['new_position'] > $this->pastPosition) {
            $this->updateAscendingPosition();
        } else {
            $this->updateDescendingPosition();
        }

        $this->lifeEventCategory
            ->update([
                'position' => $this->data['new_position'],
            ]);
    }

    private function updateAscendingPosition(): void
    {
        $this->vault->lifeEventCategories()
            ->where('position', '>', $this->pastPosition)
            ->where('position', '<=', $this->data['new_position'])
            ->decrement('position');
    }

    private function updateDescendingPosition(): void
    {
        $this->vault->lifeEventCategories()
            ->where('position', '>=', $this->data['new_position'])
            ->where('position', '<', $this->pastPosition)
            ->increment('position');
    }
}
