<?php

namespace App\Contact\ManageGroups\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\Group;
use App\Models\GroupType;
use App\Models\GroupTypeRole;
use App\Services\BaseService;
use Carbon\Carbon;

class AddContactToGroup extends BaseService implements ServiceInterface
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
            'group_type_role_id' => 'nullable|integer',
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
     * Add a contact to a group.
     *
     * @param  array  $data
     * @return Group
     */
    public function execute(array $data): Group
    {
        $this->data = $data;
        $this->validate();

        if ($this->data['group_type_role_id'] != 0) {
            $this->group->contacts()->syncWithoutDetaching([
                $this->contact->id => ['group_type_role_id' => $this->data['group_type_role_id']],
            ]);
        } else {
            $this->group->contacts()->syncWithoutDetaching([
                $this->contact->id => ['group_type_role_id' => null],
            ]);
        }

        $this->createFeedItem();
        $this->updateLastEditedDate();

        return $this->group;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->group = Group::where('vault_id', $this->data['vault_id'])
            ->findOrFail($this->data['group_id']);

        if ($this->data['group_type_role_id'] != 0) {
            $role = GroupTypeRole::findOrFail($this->data['group_type_role_id']);

            GroupType::where('account_id', $this->data['account_id'])
                ->findOrFail($role->group_type_id);
        }
    }

    private function updateLastEditedDate(): void
    {
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }

    private function createFeedItem(): void
    {
        $feedItem = ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_ADDED_TO_GROUP,
            'description' => $this->group->name,
        ]);
        $this->group->feedItem()->save($feedItem);
    }
}
