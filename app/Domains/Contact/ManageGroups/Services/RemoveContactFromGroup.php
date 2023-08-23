<?php

namespace App\Domains\Contact\ManageGroups\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Services\QueuableService;
use Carbon\Carbon;

class RemoveContactFromGroup extends QueuableService implements ServiceInterface
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
            'contact_id' => 'required|uuid|exists:contacts,id',
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
     * Remove a contact from a group.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $this->group->contacts()->detach([
            $this->contact->id,
        ]);
        $this->group->touch();

        $this->updateLastEditedDate();
        $this->createFeedItem();
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
            'action' => ContactFeedItem::ACTION_REMOVED_FROM_GROUP,
            'description' => $this->group->name,
        ]);
        $this->group->feedItem()->save($feedItem);
    }
}
