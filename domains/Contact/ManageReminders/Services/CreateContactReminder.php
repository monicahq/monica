<?php

namespace App\Contact\ManageReminders\Services;

use App\Interfaces\ServiceInterface;
use App\Jobs\CreateAuditLog;
use App\Jobs\CreateContactLog;
use App\Models\ContactReminder;
use App\Services\BaseService;
use Carbon\Carbon;

class CreateContactReminder extends BaseService implements ServiceInterface
{
    private ContactReminder $reminder;

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
     * Create a reminder.
     *
     * @param  array  $data
     * @return ContactReminder
     */
    public function execute(array $data): ContactReminder
    {
        $this->validateRules($data);
        $this->data = $data;

        $this->createContactReminder();
        $this->updateLastUpdatedDate();
        $this->scheduledReminderForAllUsersInVault();
        $this->log();

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
            (new ScheduleContactReminderForUser())->execute([
                'contact_reminder_id' => $this->reminder->id,
                'user_id' => $user->id,
            ]);
        }
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_reminder_created',
            'objects' => json_encode([
                'contact_id' => $this->contact->id,
                'contact_name' => $this->contact->name,
                'reminder_name' => $this->reminder->label,
            ]),
        ])->onQueue('low');

        CreateContactLog::dispatch([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_reminder_created',
            'objects' => json_encode([
                'reminder_name' => $this->reminder->label,
            ]),
        ])->onQueue('low');
    }
}
