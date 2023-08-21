<?php

namespace App\Domains\Contact\ManageGroups\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\Group;
use App\Models\GroupTypeRole;
use App\Services\BaseService;
use Carbon\Carbon;

class AddContactToGroup extends BaseService implements ServiceInterface
{
    private array $data;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'group_id' => 'required|integer|exists:groups,id',
            'contact_id' => 'required|uuid|exists:contacts,id',
            'group_type_role_id' => 'nullable|integer',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_vault_editor',
            'contact_must_belong_to_vault',
            'group_must_belong_to_vault',
        ];
    }

    /**
     * Add a contact to a group.
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

        $this->group->touch();

        $this->updateLastEditedDate();
        $this->createFeedItem();

        return $this->group;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        if ($this->data['group_type_role_id'] != 0) {
            $role = GroupTypeRole::findOrFail($this->data['group_type_role_id']);

            $this->account()->groupTypes()
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
