<?php

namespace App\Services\Contact\Reminder;

use App\Helpers\DateHelper;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Validation\Rule;
use App\Models\Contact\Reminder;
use App\Models\Instance\SpecialDate;

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
     * Destroy a reminder.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        $this->validate($data);

        $reminder = Reminder::where('account_id', $data['account_id'])
            ->findOrFail($data['reminder_id']);

        // remove any existing scheduled reminders
        $reminder->reminderOutboxes->each->delete();

        $reminder->delete();

        return true;
    }
}
