<?php

namespace App\Services\Contact\ManageContact;

use App\Models\Gender;
use App\Models\Contact;
use App\Models\Pronoun;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Jobs\CreateContactLog;
use App\Interfaces\ServiceInterface;

class CreateContact extends BaseService implements ServiceInterface
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'maiden_name' => 'nullable|string|max:255',
            'gender_id' => 'nullable|integer|exists:genders,id',
            'pronoun_id' => 'nullable|integer|exists:pronouns,id',
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
        $this->data = $data;

        $this->validate();
        $this->create();
        $this->log();

        return $this->contact;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        if ($this->valueOrNull($this->data, 'gender_id')) {
            Gender::where('account_id', $this->data['account_id'])
                ->findOrFail($this->data['gender_id']);
        }

        if ($this->valueOrNull($this->data, 'pronoun_id')) {
            Pronoun::where('account_id', $this->data['account_id'])
                ->findOrFail($this->data['pronoun_id']);
        }
    }

    private function create(): void
    {
        $this->contact = Contact::create([
            'vault_id' => $this->data['vault_id'],
            'first_name' => $this->data['first_name'],
            'last_name' => $this->valueOrNull($this->data, 'last_name'),
            'middle_name' => $this->valueOrNull($this->data, 'middle_name'),
            'nickname' => $this->valueOrNull($this->data, 'nickname'),
            'maiden_name' => $this->valueOrNull($this->data, 'maiden_name'),
            'gender_id' => $this->valueOrNull($this->data, 'gender_id'),
            'pronoun_id' => $this->valueOrNull($this->data, 'pronoun_id'),
        ]);
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
