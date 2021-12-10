<?php

namespace App\Services\Contact\ManageContact;

use App\Models\Contact;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Jobs\CreateContactLog;
use App\Interfaces\ServiceInterface;

class CreateContact extends BaseService implements ServiceInterface
{
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'surname' => 'nullable|string|max:255',
            'maiden_name' => 'nullable|string|max:255',
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
        ];
    }

    /**
     * Create a contact.
     *
     * @param  array  $data
     * @return Contact
     */
    public function execute(array $data): Contact
    {
        $this->validateRules($data);

        $this->contact = Contact::create([
            'vault_id' => $data['vault_id'],
            'first_name' => $data['first_name'],
            'last_name' => $this->valueOrNull($data, 'last_name'),
            'middle_name' => $this->valueOrNull($data, 'middle_name'),
            'surname' => $this->valueOrNull($data, 'surname'),
            'maiden_name' => $this->valueOrNull($data, 'maiden_name'),
        ]);

        $this->log();

        return $this->contact;
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_created',
            'objects' => json_encode([
                'id' => $this->contact->id,
                'name' => $this->contact->name,
            ]),
        ]);

        CreateContactLog::dispatch([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_created',
            'objects' => json_encode([]),
        ]);
    }
}
