<?php

namespace App\Domains\Contact\ManageContactInformation\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\ContactInformation;
use App\Services\BaseService;
use Carbon\Carbon;

class DestroyContactInformation extends BaseService implements ServiceInterface
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
            'contact_information_id' => 'required|integer|exists:contact_information,id',
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
     * Destroy a contact information.
     */
    public function execute(array $data): void
    {
        $this->data = $data;
        $this->validate();

        $this->contactInformation->delete();

        $this->updateLastEditedDate();

        $this->createFeedItem();
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->contactInformation = $this->contact->contactInformations()
            ->findOrFail($this->data['contact_information_id']);

        $this->account()->contactInformationTypes()
            ->findOrFail($this->contactInformation->contactInformationType->id);
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
            'action' => ContactFeedItem::ACTION_CONTACT_INFORMATION_DESTROYED,
            'description' => $this->contactInformation->name,
        ]);
    }
}
