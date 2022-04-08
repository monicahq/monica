<?php

namespace App\Contact\ManageContactInformation\Services;

use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Jobs\CreateContactLog;
use App\Models\ContactInformation;
use App\Interfaces\ServiceInterface;
use App\Models\ContactInformationType;

class CreateContactInformation extends BaseService implements ServiceInterface
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
            'author_must_be_vault_editor',
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Create a contact information.
     *
     * @param  array  $data
     * @return ContactInformation
     */
    public function execute(array $data): ContactInformation
    {
        $this->validateRules($data);

        $this->contactInformationType = ContactInformationType::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_information_type_id']);

        $this->contactInformation = ContactInformation::create([
            'contact_id' => $this->contact->id,
            'type_id' => $this->contactInformationType->id,
            'data' => $data['data'],
        ]);

        $this->log();

        return $this->contactInformation;
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_information_created',
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
            'action_name' => 'contact_information_created',
            'objects' => json_encode([
                'contact_information_type_name' => $this->contactInformationType->name,
            ]),
        ])->onQueue('low');
    }
}
