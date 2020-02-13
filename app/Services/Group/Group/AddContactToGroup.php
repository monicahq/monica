<?php

namespace App\Services\Group\Group;

use Carbon\Carbon;
use App\Models\Group\Group;
use App\Services\BaseService;
use App\Models\Contact\Contact;

class AddContactToGroup extends BaseService
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
            'contact_id' => 'required|integer|exists:contacts,id',
        ];
    }

    /**
     * Attach or detach contacts to a group.
     *
     * @param array $data
     * @return Group
     */
    public function execute(array $data): Group
    {
        $this->validate($data);

        $group = Group::where('account_id', $data['account_id'])
            ->findOrFail($data['group_id']);

        $this->attach($data, $group);

        return $group;
    }

    /**
     * Create the association between the contact and the group.
     *
     * @param array $data
     * @param Group $group
     * @return void
     */
    private function attach(array $data, Group $group): void
    {
        Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        // attaching should be done if the contact is not already attached
        if (! $group->contacts->contains($data['contact_id'])) {
            $group->contacts()->attach(
                $data['contact_id'],
                [
                    'created_at' => Carbon::now('UTC'),
                ]
            );
        }
    }
}
