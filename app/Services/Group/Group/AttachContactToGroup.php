<?php

namespace App\Services\Group\Group;

use App\Models\Group\Group;
use App\Services\BaseService;
use App\Models\Contact\Contact;

class AttachContactToGroup extends BaseService
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
            'contacts' => 'required|array',
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
     * Create the association.
     *
     * @param array $data
     * @param Group $group
     * @return void
     */
    private function attach(array $data, Group $group): void
    {
        // reset current associations
        $group->contacts()->sync([]);

        foreach ($data['contacts'] as $contactId) {
            Contact::where('account_id', $data['account_id'])
                ->findOrFail($contactId);

            $group->contacts()->syncWithoutDetaching([$contactId]);
        }
    }
}
