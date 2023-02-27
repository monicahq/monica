<?php

namespace App\Domains\Contact\ManageReligion\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\Religion;
use App\Services\BaseService;
use Carbon\Carbon;

class UpdateReligion extends BaseService implements ServiceInterface
{
    private Religion $religion;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'vault_id' => 'required|integer|exists:vaults,id',
            'author_id' => 'required|integer|exists:users,id',
            'contact_id' => 'required|integer|exists:contacts,id',
            'religion_id' => 'nullable|integer|exists:religions,id',
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
     * Update religion of the given contact.
     */
    public function execute(array $data): Contact
    {
        $this->validateRules($data);

        $this->religion = $this->account()->religions()
            ->findOrFail($data['religion_id']);

        $this->contact->religion_id = $data['religion_id'] ? $this->religion->id : null;
        $this->contact->save();

        $this->updateLastEditedDate();

        ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_RELIGION_UPDATED,
        ]);

        return $this->contact;
    }

    private function updateLastEditedDate(): void
    {
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }
}
