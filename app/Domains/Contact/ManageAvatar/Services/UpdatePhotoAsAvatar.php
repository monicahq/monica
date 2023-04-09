<?php

namespace App\Domains\Contact\ManageAvatar\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\File;
use App\Services\BaseService;
use Carbon\Carbon;

class UpdatePhotoAsAvatar extends BaseService implements ServiceInterface
{
    private File $file;

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
            'contact_id' => 'required|uuid|exists:contacts,id',
            'file_id' => 'nullable|integer|exists:files,id',
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
     * Set the given photo as the contact's avatar.
     */
    public function execute(array $data): Contact
    {
        $this->data = $data;
        $this->validate();

        $this->deleteCurrentAvatar();
        $this->setAvatar();
        $this->updateLastEditedDate();
        $this->createFeedItem();

        return $this->contact;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->file = $this->vault->files()
            ->where('type', File::TYPE_AVATAR)
            ->findOrFail($this->data['file_id']);
    }

    private function deleteCurrentAvatar(): void
    {
        if ($this->contact->file) {
            $this->contact->file->delete();
        }
    }

    private function setAvatar(): void
    {
        $this->contact->file_id = $this->file->id;
        $this->contact->save();

        $this->contact->files()->save($this->file);
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
