<?php

namespace App\Services\Group\Group;

use App\Models\Group\Group;
use App\Services\BaseService;

class CreateGroup extends BaseService
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
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Create a group.
     *
     * @param array $data
     * @return Group
     */
    public function execute(array $data) : Group
    {
        $this->validate($data);

        $contact = Group::create($data);

        return $contact;
    }
}
