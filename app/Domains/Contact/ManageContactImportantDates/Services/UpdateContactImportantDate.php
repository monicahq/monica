<?php

namespace App\Domains\Contact\ManageContactImportantDates\Services;

use App\Helpers\ImportantDateHelper;
use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\ContactImportantDate;
use App\Services\BaseService;
use Carbon\Carbon;

class UpdateContactImportantDate extends BaseService implements ServiceInterface
{
    private ContactImportantDate $date;

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
            'contact_important_date_id' => 'required|integer|exists:contact_important_dates,id',
            'contact_important_date_type_id' => 'nullable|integer|exists:contact_important_date_types,id',
            'label' => 'required|string|max:255',
            'day' => 'nullable|integer',
            'month' => 'nullable|integer',
            'year' => 'nullable|integer',
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
     * Update a contact date.
     */
    public function execute(array $data): ContactImportantDate
    {
        $this->data = $data;
        $this->validate();
        $this->update();
        $this->createFeedItem();

        return $this->date;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->date = $this->contact->importantDates()
            ->findOrFail($this->data['contact_important_date_id']);

        // make sure the vault matches
        if ($this->valueOrNull($this->data, 'contact_important_date_type_id')) {
            $this->vault->contactImportantDateTypes()
                ->findOrFail($this->data['contact_important_date_type_id']);
        }
    }

    private function update(): void
    {
        $this->date->label = $this->data['label'];
        $this->date->contact_important_date_type_id = $this->valueOrNull($this->data, 'contact_important_date_type_id');
        $this->date->day = $this->valueOrNull($this->data, 'day');
        $this->date->month = $this->valueOrNull($this->data, 'month');
        $this->date->year = $this->valueOrNull($this->data, 'year');
        $this->date->save();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }

    private function createFeedItem(): void
    {
        $feedItem = ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_IMPORTANT_DATE_UPDATED,
            'description' => $this->date->label.' '.ImportantDateHelper::formatDate($this->date, $this->author),
        ]);

        $this->date->feedItem()->save($feedItem);
    }
}
