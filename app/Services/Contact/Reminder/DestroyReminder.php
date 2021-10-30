<?php

namespace App\Services\Contact\Reminder;

use App\Services\BaseService;
use App\Models\Contact\Reminder;

class DestroyReminder extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'reminder_id' => 'required|integer|exists:reminders,id',
        ];
    }

    /**
     * Destroy a reminder and all scheduled reminders that are associated with
     * it (in ReminderOutbox table) thanks to foreign keys.
     *
     * @param  array  $data
     * @return bool
     */
    public function execute(array $data): bool
    {
        $this->validate($data);

        $reminder = Reminder::where('account_id', $data['account_id'])
            ->findOrFail($data['reminder_id']);

        $reminder->contact->throwInactive();

        $reminder->delete();

        return true;
    }
}
