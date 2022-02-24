<?php

namespace App\Services\Contact\ManageContactImportantDate;

use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Jobs\CreateContactLog;
use App\Interfaces\ServiceInterface;
use App\Models\ContactImportantDate;

class CreateContactImportantDate extends BaseService implements ServiceInterface
{
    private ContactImportantDate $date;

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
            'label' => 'required|string|max:255',
            'day' => 'nullable|integer',
            'month' => 'nullable|integer',
            'year' => 'nullable|integer',
            'type' => 'nullable|string|max:255',
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
     * Create a contact date.
     *
     * @param  array  $data
     * @return ContactImportantDate
     */
    public function execute(array $data): ContactImportantDate
    {
        $this->validateRules($data);

        $this->date = ContactImportantDate::create([
            'contact_id' => $data['contact_id'],
            'label' => $data['label'],
            'day' => $this->valueOrNull($data, 'day'),
            'month' => $this->valueOrNull($data, 'month'),
            'year' => $this->valueOrNull($data, 'year'),
            'type' => $this->valueOrNull($data, 'type'),
        ]);

        $this->log();

        return $this->date;
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_date_created',
            'objects' => json_encode([
                'contact_id' => $this->contact->id,
                'contact_name' => $this->contact->name,
                'label' => $this->date->label,
            ]),
        ])->onQueue('low');

        CreateContactLog::dispatch([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_date_created',
            'objects' => json_encode([
                'label' => $this->date->label,
            ]),
        ])->onQueue('low');
    }
}
