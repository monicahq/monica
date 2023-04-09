<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Interfaces\ServiceInterface;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Services\BaseService;

class UpdateLifeEventTypePosition extends BaseService implements ServiceInterface
{
    private LifeEventCategory $lifeEventCategory;

    private LifeEventType $lifeEventType;

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
            'life_event_type_id' => 'required|integer|exists:life_event_types,id',
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
    public function execute(array $data): LifeEventType
    {
        $this->data = $data;
        $this->validate();
        $this->updatePosition();

        return $this->lifeEventType;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->lifeEventCategory = $this->vault->lifeEventCategories()
            ->findOrFail($this->data['life_event_category_id']);

        $this->lifeEventType = $this->lifeEventCategory->lifeEventTypes()
            ->findOrFail($this->data['life_event_type_id']);

        $this->pastPosition = $this->lifeEventType->position;
    }

    private function updatePosition(): void
    {
        if ($this->data['new_position'] > $this->pastPosition) {
            $this->updateAscendingPosition();
        } else {
            $this->updateDescendingPosition();
        }

        $this->lifeEventType
            ->update([
                'position' => $this->data['new_position'],
            ]);
    }

    private function updateAscendingPosition(): void
    {
        $this->lifeEventCategory->lifeEventTypes()
            ->where('position', '>', $this->pastPosition)
            ->where('position', '<=', $this->data['new_position'])
            ->decrement('position');
    }

    private function updateDescendingPosition(): void
    {
        $this->lifeEventCategory->lifeEventTypes()
            ->where('position', '>=', $this->data['new_position'])
            ->where('position', '<', $this->pastPosition)
            ->increment('position');
    }
}
