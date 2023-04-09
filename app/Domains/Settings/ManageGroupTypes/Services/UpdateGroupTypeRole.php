<?php

namespace App\Domains\Settings\ManageGroupTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GroupTypeRole;
use App\Services\BaseService;

class UpdateGroupTypeRole extends BaseService implements ServiceInterface
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
            'group_type_role_id' => 'required|integer|exists:group_type_roles,id',
            'label' => 'required|string|max:255',
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
     * Update a group type role.
     */
    public function execute(array $data): GroupTypeRole
    {
        $this->data = $data;
        $this->validate();
        $this->update();

        return $this->groupTypeRole;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $groupType = $this->account()->groupTypes()
            ->findOrFail($this->data['group_type_id']);

        $this->groupTypeRole = $groupType->groupTypeRoles()
            ->findOrFail($this->data['group_type_role_id']);
    }

    private function update(): void
    {
        $this->groupTypeRole->label = $this->data['label'];
        $this->groupTypeRole->save();
    }
}
