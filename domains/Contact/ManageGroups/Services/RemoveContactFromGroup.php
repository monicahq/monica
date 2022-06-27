<?php

namespace App\Contact\ManageGroups\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\Group;
use App\Services\BaseService;

class RemoveContactFromGroup extends BaseService implements ServiceInterface
{
    private Group $group;
    private array $data;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'vault_id' => 'required|integer|exists:vaults,id',
            'author_id' => 'required|integer|exists:users,id',
            'group_id' => 'required|integer|exists:groups,id',
            'contact_id' => 'required|integer|exists:contacts,id',
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
            'vault_must_belong_to_account',
            'author_must_be_vault_editor',
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Remove a contact from a group.
     *
     * @param  array  $data
     * @return Group
     */
    public function execute(array $data): Group
    {
        $this->data = $data;
        $this->validate();

        $this->group->contacts()->detach([
            $this->contact->id,
        ]);

        return $this->group;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->group = Group::where('vault_id', $this->data['vault_id'])
            ->findOrFail($this->data['group_id']);
    }
}
