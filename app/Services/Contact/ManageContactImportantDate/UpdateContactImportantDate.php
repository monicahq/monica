<?php

namespace App\Services\Contact\ManageContactImportantDate;

use Carbon\Carbon;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Jobs\CreateContactLog;
use App\Models\ContactFeedItem;
use App\Interfaces\ServiceInterface;
use App\Models\ContactImportantDate;

class UpdateContactImportantDate extends BaseService implements ServiceInterface
{
    private ContactImportantDate $date;
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
            'contact_important_date_id' => 'required|integer|exists:contact_important_dates,id',
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
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Update a contact date.
     *
     * @param  array  $data
     * @return ContactImportantDate
     */
    public function execute(array $data): ContactImportantDate
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

        $this->date = ContactImportantDate::where('contact_id', $this->contact->id)
            ->findOrFail($this->data['contact_important_date_id']);
    }

    private function update(): void
    {
        $this->date->label = $this->data['label'];
        $this->date->day = $this->valueOrNull($this->data, 'day');
        $this->date->month = $this->valueOrNull($this->data, 'month');
        $this->date->year = $this->valueOrNull($this->data, 'year');
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
        ])->onQueue('low');

        CreateContactLog::dispatch([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_date_updated',
            'objects' => json_encode([
                'label' => $this->date->label,
            ]),
        ])->onQueue('low');

        ContactFeedItem::create([
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_IMPORTANT_DATE_UPDATED,
        ]);
    }
}
