<?php

namespace App\Settings\ManageGroupTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GroupType;
use App\Models\GroupTypeRole;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class UpdateGroupTypeRolePosition extends BaseService implements ServiceInterface
{
    private GroupTypeRole $groupTypeRole;

    private int $pastPosition;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'group_type_id' => 'required|integer|exists:group_types,id',
            'group_type_role_id' => 'required|integer|exists:group_type_roles,id',
            'new_position' => 'required|integer',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
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
     *
     * @param  array  $data
     * @return GroupTypeRole
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

        GroupType::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['group_type_id']);

        $this->groupTypeRole = GroupTypeRole::where('group_type_id', $this->data['group_type_id'])
            ->findOrFail($this->data['group_type_role_id']);

        $this->pastPosition = DB::table('group_type_roles')
            ->where('id', $this->groupTypeRole->id)
            ->select('position')
            ->first()->position;
    }

    private function updatePosition(): void
    {
        if ($this->data['new_position'] > $this->pastPosition) {
            $this->updateAscendingPosition();
        } else {
            $this->updateDescendingPosition();
        }

        DB::table('group_type_roles')
            ->where('id', $this->groupTypeRole->id)
            ->update([
                'position' => $this->data['new_position'],
            ]);
    }

    private function updateAscendingPosition(): void
    {
        DB::table('group_type_roles')
            ->where('position', '>', $this->pastPosition)
            ->where('position', '<=', $this->data['new_position'])
            ->decrement('position');
    }

    private function updateDescendingPosition(): void
    {
        DB::table('group_type_roles')
            ->where('position', '>=', $this->data['new_position'])
            ->where('position', '<', $this->pastPosition)
            ->increment('position');
    }
}
