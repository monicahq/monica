<?php

namespace App\Services\Contact\LifeEvent;

use App\Services\BaseService;
use Illuminate\Validation\Rule;
use App\Models\Contact\Reminder;
use App\Models\Contact\LifeEvent;
use App\Services\Contact\Reminder\CreateReminder;

class AddReminderToLifeEvent extends BaseService
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
            'life_event_id' => 'required|integer',
            'date' => 'required|date',
            'frequency_type' => [
                'required',
                Rule::in(Reminder::$frequencyTypes),
            ],
            'frequency_number' => 'required|integer',
        ];
    }

    /**
     * Add a reminder to the life event.
     *
     * @param array $data
     * @return Reminder
     */
    public function execute(array $data) : Reminder
    {
        $this->validate($data);

        $lifeEvent = LifeEvent::where('account_id', $data['account_id'])
                                ->findOrFail($data['life_event_id']);

        $request = [
            'contact_id' => $lifeEvent->contact_id,
            'account_id'  => $data['account_id'],
            'date' => $data['date'],
            'frequency_type' => $data['frequency_type'],
            'frequency_number' => $data['frequency_number'],
            'title' => $lifeEvent->lifeEventType->name,
            'description' => null,
            'special_date_id' => null,
        ];

        return (new CreateReminder)->execute($request);
    }
}
