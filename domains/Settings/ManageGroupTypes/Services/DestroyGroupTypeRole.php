<?php

namespace App\Settings\ManageGroupTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GroupType;
use App\Models\GroupTypeRole;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class DestroyGroupTypeRole extends BaseService implements ServiceInterface
{
    private GroupTypeRole $groupTypeRole;

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
     * Destroy a group type.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        GroupType::where('account_id', $data['account_id'])
            ->findOrFail($data['group_type_id']);

        $this->groupTypeRole = GroupTypeRole::where('group_type_id', $data['group_type_id'])
            ->findOrFail($data['group_type_role_id']);

        $this->groupTypeRole->delete();

        $this->repositionEverything();
    }

    private function repositionEverything(): void
    {
        DB::table('group_type_roles')->where('position', '>', $this->groupTypeRole->position)->decrement('position');
    }
}
