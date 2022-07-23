<?php

namespace App\Contact\ManageContactInformation\Services;

use App\Interfaces\ServiceInterface;
use App\Jobs\CreateAuditLog;
use App\Jobs\CreateContactLog;
use App\Models\ContactInformation;
use App\Models\ContactInformationType;
use App\Services\BaseService;
use Carbon\Carbon;

class UpdateContactInformation extends BaseService implements ServiceInterface
{
    private ContactInformation $contactInformation;
    private ContactInformationType $contactInformationType;

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
            'contact_information_type_id' => 'required|integer|exists:contact_information_types,id',
            'contact_information_id' => 'required|integer|exists:contact_information,id',
            'data' => 'required|string|max:255',
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
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Update a contact information.
     *
     * @param  array  $data
     * @return ContactInformation
     */
    public function execute(array $data): ContactInformation
    {
        $this->data = $data;
        $this->validate();
        $this->update();
        $this->updateLastEditedDate();
        $this->log();

        return $this->contactInformation;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->contactInformationType = ContactInformationType::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['contact_information_type_id']);

        $this->contactInformation = ContactInformation::where('contact_id', $this->contact->id)
            ->findOrFail($this->data['contact_information_id']);
    }

    private function update(): void
    {
        $this->contactInformation->data = $this->data['data'];
        $this->contactInformation->type_id = $this->data['contact_information_type_id'];
        $this->contactInformation->save();
    }

    private function updateLastEditedDate(): void
    {
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_information_updated',
            'objects' => json_encode([
                'contact_id' => $this->contact->id,
                'contact_name' => $this->contact->name,
                'contact_information_type_name' => $this->contactInformationType->name,
            ]),
        ])->onQueue('low');

        CreateContactLog::dispatch([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_information_updated',
            'objects' => json_encode([
                'contact_information_type_name' => $this->contactInformationType->name,
            ]),
        ])->onQueue('low');
    }
}
