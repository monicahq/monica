<?php

namespace App\Domains\Contact\ManageContactImportantDates\Services;

use App\Helpers\ImportantDateHelper;
use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\ContactImportantDate;
use App\Services\BaseService;
use Carbon\Carbon;

class DestroyContactImportantDate extends BaseService implements ServiceInterface
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
            'contact_important_date_id' => 'required|integer|exists:contact_important_dates,id',
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
     * Delete a contact address.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $this->date = $this->contact->importantDates()
            ->findOrFail($data['contact_important_date_id']);

        $this->date->delete();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        $this->createFeedItem();
    }

    private function createFeedItem(): void
    {
        ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_IMPORTANT_DATE_DESTROYED,
            'description' => $this->date->label.' '.ImportantDateHelper::formatDate($this->date, $this->author),
        ]);
    }
}
