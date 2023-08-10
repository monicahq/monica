<?php

namespace App\Domains\Contact\ManageGroups\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\GroupTypeRole;
use App\Services\QueuableService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddContactToGroup extends QueuableService implements ServiceInterface
{
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
            'contact_id' => 'required_if:contact_distant_uuid,null|uuid|exists:contacts,id',
            'contact_distant_uuid' => 'required_if:contact_id,null|string',
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
        ];
    }

    /**
     * Add a contact to a group.
     */
    public function execute(array $data): void
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

        $this->createFeedItem();
        $this->updateLastEditedDate();
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        if (isset($this->data['contact_id'])) {
            $this->validateContactBelongsToVault($this->data);
        } else {
            $this->contact = $this->vault->contacts()
                ->where('distant_uuid', $this->data['contact_distant_uuid'])
                ->firstOrFail();

            if ($this->contact->vault_id !== $this->vault->id) {
                throw new ModelNotFoundException();
            }
        }

        $this->group = $this->vault->groups()
            ->findOrFail($this->data['group_id']);

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
