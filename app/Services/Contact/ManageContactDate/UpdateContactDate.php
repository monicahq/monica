<?php

namespace App\Services\Contact\ManageContactDate;

use Carbon\Carbon;
use App\Models\ContactDate;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Jobs\CreateContactLog;
use App\Interfaces\ServiceInterface;

class UpdateContactDate extends BaseService implements ServiceInterface
{
    private ContactDate $date;
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
            'contact_id' => 'required|integer|exists:contacts,id',
            'contact_date_id' => 'required|integer|exists:contact_dates,id',
            'label' => 'required|string|max:255',
            'date' => 'required|string|max:255',
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
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Update a contact date.
     *
     * @param  array  $data
     * @return ContactDate
     */
    public function execute(array $data): ContactDate
    {
        $this->data = $data;
        $this->validate();
        $this->update();
        $this->log();

        return $this->date;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->date = ContactDate::where('contact_id', $this->contact->id)
            ->findOrFail($this->data['contact_date_id']);
    }

    private function update(): void
    {
        $this->date->label = $this->data['label'];
        $this->date->date = $this->data['date'];
        $this->date->type = $this->valueOrNull($this->data, 'type');
        $this->date->save();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_date_updated',
            'objects' => json_encode([
                'contact_id' => $this->contact->id,
                'contact_name' => $this->contact->name,
                'label' => $this->date->label,
            ]),
        ]);

        CreateContactLog::dispatch([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_date_updated',
            'objects' => json_encode([
                'label' => $this->date->label,
            ]),
        ]);
    }
}
