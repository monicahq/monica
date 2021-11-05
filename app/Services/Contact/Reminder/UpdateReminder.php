<?php

namespace App\Services\Contact\Reminder;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Validation\Rule;
use App\Models\Contact\Reminder;

class UpdateReminder extends BaseService
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
            'contact_id' => 'required|integer|exists:contacts,id',
            'reminder_id' => 'required|integer|exists:reminders,id',
            'initial_date' => 'required|date_format:Y-m-d',
            'frequency_type' => [
                'required',
                Rule::in(Reminder::$frequencyTypes),
            ],
            'frequency_number' => 'nullable|integer',
            'title' => 'required|string|max:100000',
            'description' => 'nullable|max:1000000',
            'delible' => 'nullable|boolean',
        ];
    }

    /**
     * Update a reminder.
     *
     * @param  array  $data
     * @return Reminder
     */
    public function execute(array $data): Reminder
    {
        $this->validate($data);

        /** @var Reminder */
        $reminder = Reminder::where('account_id', $data['account_id'])
            ->where('contact_id', $data['contact_id'])
            ->findOrFail($data['reminder_id']);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $contact->throwInactive();

        $reminder->update([
            'title' => $data['title'],
            'description' => $this->nullOrValue($data, 'description'),
            'initial_date' => $data['initial_date'],
            'frequency_type' => $data['frequency_type'],
            'frequency_number' => $this->nullOrValue($data, 'frequency_number'),
            'delible' => (isset($data['delible']) ? $data['delible'] : true),
        ]);

        foreach ($reminder->account->users as $user) {
            $reminder->schedule($user);
        }

        return $reminder;
    }
}
