<?php

namespace App\Services\Group\Group;

use App\Models\Group\Group;
use App\Services\BaseService;

class UpdateGroup extends BaseService
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
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Update a group.
     *
     * @param array $data
     * @return Group
     */
    public function execute(array $data): Group
    {
        $this->validate($data);

        $group = Group::where('account_id', $data['account_id'])
            ->findOrFail($data['group_id']);

        $group->name = $data['name'];
        $group->save();

        return $group;
    }
}
