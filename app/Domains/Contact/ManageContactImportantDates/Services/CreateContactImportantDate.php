<?php

namespace App\Domains\Contact\ManageContactImportantDates\Services;

use App\Helpers\ImportantDateHelper;
use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\ContactImportantDate;
use App\Services\BaseService;
use Carbon\Carbon;

class CreateContactImportantDate extends BaseService implements ServiceInterface
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
            'author_must_be_vault_editor',
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Create a contact date.
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
        $this->createFeedItem();

        return $this->date;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        // make sure the vault matches
        if (! is_null($this->valueOrNull($this->data, 'contact_important_date_type_id'))) {
            $this->vault->contactImportantDateTypes()
                ->findOrFail($this->data['contact_important_date_type_id']);
        }
    }

    private function updateLastEditedDate(): void
    {
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
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
