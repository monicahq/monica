<?php

namespace App\Domains\Contact\ManageReminders\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactReminder;
use App\Services\BaseService;
use Carbon\Carbon;

class CreateContactReminder extends BaseService implements ServiceInterface
{
    private ContactReminder $reminder;

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
            'label' => 'required|string|max:255',
            'day' => 'nullable|integer',
            'month' => 'nullable|integer',
            'year' => 'nullable|integer',
            'type' => 'required|string:255',
            'frequency_number' => 'nullable|integer',
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
     * Create a reminder.
     */
    public function execute(array $data): ContactReminder
    {
        $this->validateRules($data);
        $this->data = $data;

        $this->createContactReminder();
        $this->updateLastUpdatedDate();
        $this->scheduledReminderForAllUsersInVault();

        return $this->reminder;
    }

    private function createContactReminder(): void
    {
        $this->reminder = ContactReminder::create([
            'contact_id' => $this->data['contact_id'],
            'label' => $this->data['label'],
            'day' => $this->valueOrNull($this->data, 'day'),
            'month' => $this->valueOrNull($this->data, 'month'),
            'year' => $this->valueOrNull($this->data, 'year'),
            'type' => $this->data['type'],
            'frequency_number' => $this->valueOrNull($this->data, 'frequency_number'),
        ]);
    }

    private function updateLastUpdatedDate(): void
    {
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }

    private function scheduledReminderForAllUsersInVault(): void
    {
        $users = $this->vault->users()->get();

        foreach ($users as $user) {
            (new ScheduleContactReminderForUser)->execute([
                'contact_reminder_id' => $this->reminder->id,
                'user_id' => $user->id,
            ]);
        }
    }
}
