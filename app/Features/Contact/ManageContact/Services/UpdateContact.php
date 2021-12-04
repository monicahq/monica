<?php

namespace App\Features\Contact\ManageContact\Services;

use App\Models\User;
use App\Models\Contact;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Jobs\CreateContactLog;
use App\Interfaces\ServiceInterface;

class UpdateContact extends BaseService implements ServiceInterface
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
            'contact_id' => 'required|integer|exists:contacts,id',
            'last_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'surname' => 'nullable|string|max:255',
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
     * Update a contact.
     *
     * @param array $data
     * @return Contact
     */
    public function execute(array $data): Contact
    {
        $this->validateRules($data);

        $this->contact->first_name = $data['first_name'];
        $this->contact->last_name = $this->valueOrNull($data, 'last_name');
        $this->contact->middle_name = $this->valueOrNull($data, 'middle_name');
        $this->contact->surname = $this->valueOrNull($data, 'surname');
        $this->contact->save();

        $this->log();

        return $this->contact;
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_updated',
            'objects' => json_encode([
                'id' => $this->contact->id,
                'name' => $this->contact->name,
            ]),
        ]);

        CreateContactLog::dispatch([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_updated',
            'objects' => json_encode([]),
        ]);
    }
}
