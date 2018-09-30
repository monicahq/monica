<?php

namespace App\Services\Contact\LifeEvent;

use App\Services\BaseService;
use App\Models\Contact\Reminder;
use App\Models\Contact\LifeEvent;
use App\Exceptions\MissingParameterException;
use App\Services\Contact\Reminder\CreateReminder;

class AddReminderToLifeEvent extends BaseService
{
    /**
     * The structure that the method expects to receive as parameter.
     *
     * @var array
     */
    private $structure = [
        'account_id',
        'life_event_id',
        'date',
        'frequency_type',
        'frequency_number',
    ];

    /**
     * Add a reminder to the life event.
     *
     * @param array $data
     * @return Reminder
     */
    public function execute(array $data) : Reminder
    {
        if (! $this->validateDataStructure($data, $this->structure)) {
            throw new MissingParameterException('Missing parameters');
        }

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
