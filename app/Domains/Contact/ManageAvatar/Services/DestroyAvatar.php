<?php

namespace App\Domains\Contact\ManageAvatar\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Services\BaseService;
use Carbon\Carbon;

class DestroyAvatar extends BaseService implements ServiceInterface
{
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
     * Remove the current file used as avatar and put the default avatar back.
     *
     * @param  array  $data
     * @return Contact
     */
    public function execute(array $data): Contact
    {
        $this->data = $data;
        $this->validate();

        $this->deleteCurrentAvatar();
        $this->updateLastEditedDate();
        $this->createFeedItem();

        return $this->contact;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }

    private function deleteCurrentAvatar(): void
    {
        if ($this->contact->file) {
            $this->contact->file->delete();
        }

        $this->contact->file_id = null;
    }

    private function updateLastEditedDate(): void
    {
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }

    private function createFeedItem(): void
    {
        ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_CHANGE_AVATAR,
        ]);
    }
}
