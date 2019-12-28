<?php

namespace App\Services\Group\Group;

use App\Models\Group\Group;
use App\Services\BaseService;

class DestroyGroup extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'group_id' => 'required|integer|exists:groups,id',
        ];
    }

    /**
     * Destroy a group.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data)
    {
        $this->validate($data);

        $group = Group::where('account_id', $data['account_id'])
            ->findOrFail($data['group_id']);

        $group->delete();

        return true;
    }
}
