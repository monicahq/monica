<?php

namespace App\Contact\ManageContactImportantDates\Services;

use App\Helpers\ImportantDateHelper;
use App\Interfaces\ServiceInterface;
use App\Jobs\CreateAuditLog;
use App\Jobs\CreateContactLog;
use App\Models\ContactFeedItem;
use App\Models\ContactImportantDate;
use App\Models\ContactImportantDateType;
use App\Services\BaseService;
use Carbon\Carbon;

class CreateContactImportantDate extends BaseService implements ServiceInterface
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
            'contact_important_date_type_id' => 'nullable|integer|exists:contact_important_date_types,id',
            'label' => 'required|string|max:255',
            'day' => 'nullable|integer',
            'month' => 'nullable|integer',
            'year' => 'nullable|integer',
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
        $this->data = $data;
        $this->validate();

        $this->date = ContactImportantDate::create([
            'contact_id' => $data['contact_id'],
            'contact_important_date_type_id' => $this->valueOrNull($data, 'contact_important_date_type_id'),
            'label' => $data['label'],
            'day' => $this->valueOrNull($data, 'day'),
            'month' => $this->valueOrNull($data, 'month'),
            'year' => $this->valueOrNull($data, 'year'),
        ]);

        $this->updateLastEditedDate();
        $this->log();
        $this->createFeedItem();

        return $this->date;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        // make sure the vault matches
        if (! is_null($this->valueOrNull($this->data, 'contact_important_date_type_id'))) {
            ContactImportantDateType::where('vault_id', $this->data['vault_id'])
                ->findOrFail($this->data['contact_important_date_type_id']);
        }
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

    private function createFeedItem(): void
    {
        $feedItem = ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_IMPORTANT_DATE_CREATED,
            'description' => $this->date->label.' '.ImportantDateHelper::formatDate($this->date, $this->author),
        ]);

        $this->date->feedItem()->save($feedItem);
    }
}
