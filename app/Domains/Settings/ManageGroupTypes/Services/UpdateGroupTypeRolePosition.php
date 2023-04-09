<?php

namespace App\Domains\Settings\ManageGroupTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GroupType;
use App\Models\GroupTypeRole;
use App\Services\BaseService;

class UpdateGroupTypeRolePosition extends BaseService implements ServiceInterface
{
    private GroupType $groupType;

    private GroupTypeRole $groupTypeRole;

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
            'group_type_id' => 'required|integer|exists:group_types,id',
            'group_type_role_id' => 'required|integer|exists:group_type_roles,id',
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
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Update the group type role's position.
     */
    public function execute(array $data): GroupTypeRole
    {
        $this->data = $data;
        $this->validate();
        $this->updatePosition();

        return $this->groupTypeRole;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->groupType = $this->account()->groupTypes()
            ->findOrFail($this->data['group_type_id']);

        $this->groupTypeRole = $this->groupType->groupTypeRoles()
            ->findOrFail($this->data['group_type_role_id']);

        $this->pastPosition = $this->groupTypeRole->position;
    }

    private function updatePosition(): void
    {
        if ($this->data['new_position'] > $this->pastPosition) {
            $this->updateAscendingPosition();
        } else {
            $this->updateDescendingPosition();
        }

        $this->groupTypeRole
            ->update([
                'position' => $this->data['new_position'],
            ]);
    }

    private function updateAscendingPosition(): void
    {
        $this->groupType->groupTypeRoles()
            ->where('position', '>', $this->pastPosition)
            ->where('position', '<=', $this->data['new_position'])
            ->decrement('position');
    }

    private function updateDescendingPosition(): void
    {
        $this->groupType->groupTypeRoles()
            ->where('position', '>=', $this->data['new_position'])
            ->where('position', '<', $this->pastPosition)
            ->increment('position');
    }
}
