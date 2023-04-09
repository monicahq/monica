<?php

namespace App\Domains\Settings\ManageGroupTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GroupType;
use App\Models\GroupTypeRole;
use App\Services\BaseService;

class DestroyGroupTypeRole extends BaseService implements ServiceInterface
{
    private GroupType $groupType;

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
     * Destroy a group type.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $this->groupType = $this->account()->groupTypes()
            ->findOrFail($data['group_type_id']);

        $this->groupTypeRole = $this->groupType->groupTypeRoles()
            ->findOrFail($data['group_type_role_id']);

        $this->groupTypeRole->delete();

        $this->repositionEverything();
    }

    private function repositionEverything(): void
    {
        $this->groupType->groupTypeRoles()
            ->where('position', '>', $this->groupTypeRole->position)
            ->decrement('position');
    }
}
