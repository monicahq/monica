<?php

namespace App\Services\Contact\Reminder;

use App\Helpers\DateHelper;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Validation\Rule;
use App\Models\Contact\Reminder;
use App\Models\Instance\SpecialDate;

class CreateReminder extends BaseService
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
            'contact_id' => 'required|integer',
            'date' => 'required|date',
            'frequency_type' => [
                'required',
                Rule::in(Reminder::$frequencyTypes),
            ],
            'frequency_number' => 'required|integer',
            'title' => 'string|max:100000',
            'description' => 'nullable|max:1000000',
            'special_date_id' => 'nullable|integer',
        ];
    }

    /**
     * Create a reminder.
     *
     * @param array $data
     * @return Reminder
     */
    public function execute(array $data) : Reminder
    {
        $this->validate($data);

        Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        if ($data['special_date_id']) {
            SpecialDate::where('account_id', $data['account_id'])
                ->findOrFail($data['special_date_id']);
        }

        $reminder = $this->attachReminderToLifeEvent($data);

        $reminder->calculateNextExpectedDate()->save();
        $reminder->scheduleNotifications();

        return $reminder;
    }

    /**
     * Actually create the reminder.
     *
     * @return Reminder
     */
    private function attachReminderToLifeEvent(array $data) : Reminder
    {
        $reminder = new Reminder;
        $reminder->frequency_type = $data['frequency_type'];
        $reminder->frequency_number = $data['frequency_number'];
        $reminder->next_expected_date = DateHelper::parseDate($data['date']);
        $reminder->special_date_id = $data['special_date_id'];
        $reminder->account_id = $data['account_id'];
        $reminder->contact_id = $data['contact_id'];
        $reminder->title = $data['title'];
        $reminder->description = $data['description'];
        $reminder->save();

        return $reminder;
    }
}
