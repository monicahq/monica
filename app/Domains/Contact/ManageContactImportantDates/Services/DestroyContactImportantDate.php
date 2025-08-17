<?php

namespace App\Domains\Contact\ManageContactImportantDates\Services;

use App\Helpers\ImportantDateHelper;
use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\ContactImportantDate;
use App\Services\QueuableService;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Traits\Localizable;

class DestroyContactImportantDate extends QueuableService implements ServiceInterface
{
    use Batchable, Localizable;

    private ContactImportantDate $date;

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
     * Delete a contact important date.
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
