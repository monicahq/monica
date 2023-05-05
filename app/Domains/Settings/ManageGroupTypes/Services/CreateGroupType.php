<?php

namespace App\Domains\Settings\ManageGroupTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GroupType;
use App\Services\BaseService;

class CreateGroupType extends BaseService implements ServiceInterface
{
    private array $data;

    private GroupType $groupType;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'label' => 'nullable|string|max:255',
            'label_translation_key' => 'nullable|string|max:255',
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
     * Create a group type.
     */
    public function execute(array $data): GroupType
    {
        $this->data = $data;

        $this->validate();
        $this->create();

        return $this->groupType;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }

    private function create(): void
    {
        // determine the new position of the template page
        $newPosition = $this->account()->groupTypes()
            ->max('position');
        $newPosition++;

        $this->groupType = GroupType::create([
            'account_id' => $this->data['account_id'],
            'label' => $this->data['label'] ?? null,
            'label_translation_key' => $this->data['label_translation_key'] ?? null,
            'position' => $newPosition,
        ]);
    }
}
