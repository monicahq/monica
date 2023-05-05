<?php

namespace App\Domains\Settings\ManageGroupTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GroupTypeRole;
use App\Services\BaseService;

class CreateGroupTypeRole extends BaseService implements ServiceInterface
{
    private array $data;

    private GroupTypeRole $groupTypeRole;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'group_type_id' => 'required|integer|exists:group_types,id',
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
     * Create a group type role.
     */
    public function execute(array $data): GroupTypeRole
    {
        $this->data = $data;

        $this->validate();
        $this->create();

        return $this->groupTypeRole;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }

    private function create(): void
    {
        $groupType = $this->account()->groupTypes()
            ->findOrFail($this->data['group_type_id']);

        // determine the new position of the template page
        $newPosition = $groupType->groupTypeRoles()
            ->max('position');
        $newPosition++;

        $this->groupTypeRole = GroupTypeRole::create([
            'group_type_id' => $this->data['group_type_id'],
            'label' => $this->data['label'] ?? null,
            'label_translation_key' => $this->data['label_translation_key'] ?? null,
            'position' => $newPosition,
        ]);
    }
}
