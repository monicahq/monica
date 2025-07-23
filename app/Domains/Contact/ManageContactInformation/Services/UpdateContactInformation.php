<?php

namespace App\Domains\Contact\ManageContactInformation\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\ContactInformation;
use App\Services\BaseService;
use Carbon\Carbon;

class UpdateContactInformation extends BaseService implements ServiceInterface
{
    private ContactInformation $contactInformation;

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
            'contact_information_type_id' => 'required|integer|exists:contact_information_types,id',
            'contact_information_id' => 'required|integer|exists:contact_information,id',
            'contact_information_kind' => 'nullable|string',
            'data' => 'required|string|max:255',
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
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Update a contact information.
     */
    public function execute(array $data): ContactInformation
    {
        $this->data = $data;
        $this->validate();
        $this->update();
        $this->updateLastEditedDate();
        $this->createFeedItem();

        return $this->contactInformation;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->account()->contactInformationTypes()
            ->findOrFail($this->data['contact_information_type_id']);

        $this->contactInformation = $this->contact->contactInformations()
            ->findOrFail($this->data['contact_information_id']);
    }

    private function update(): void
    {
        $this->contactInformation->data = $this->data['data'];
        $this->contactInformation->type_id = $this->data['contact_information_type_id'];
        $this->contactInformation->kind = $this->valueOrNull($this->data, 'contact_information_kind');
        $this->contactInformation->save();
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
            'action' => ContactFeedItem::ACTION_CONTACT_INFORMATION_UPDATED,
            'description' => $this->contactInformation->name,
        ]);
        $this->contactInformation->feedItem()->save($feedItem);
    }
}
