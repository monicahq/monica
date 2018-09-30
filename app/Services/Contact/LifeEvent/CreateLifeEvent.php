<?php

namespace App\Services\Contact\LifeEvent;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Contact\LifeEvent;
use App\Models\Contact\LifeEventType;
use App\Exceptions\MissingParameterException;

class CreateLifeEvent extends BaseService
{
    /**
     * The structure that the method expects to receive as parameter.
     *
     * @var array
     */
    private $structure = [
        'account_id',
        'contact_id',
        'life_event_type_id',
        'happened_at',
        'name',
        'note',
        'has_reminder',
        'happened_at_month_unknown',
        'happened_at_day_unknown',
    ];

    /**
     * Create a life event.
     *
     * @param array $data
     * @return LifeEvent
     */
    public function execute(array $data) : LifeEvent
    {
        if (! $this->validateDataStructure($data, $this->structure)) {
            throw new MissingParameterException('Missing parameters');
        }

        Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        LifeEventType::where('account_id', $data['account_id'])
            ->findOrFail($data['life_event_type_id']);

        $lifeEvent = LifeEvent::create($data);
        $this->addYearlyReminder($data, $lifeEvent);

        // Get the newly created object as the Create method doesn't return all
        // fields by default
        return LifeEvent::find($lifeEvent->id);
    }

    /**
     * Add yearly reminder if necessary.
     *
     * @param array $data
     * @param LifeEvent $lifeEvent
     */
    private function addYearlyReminder($data, $lifeEvent)
    {
        if ($data['has_reminder'] == true) {
            $array = [
                'account_id' => $data['account_id'],
                'life_event_id' => $lifeEvent->id,
                'date' => $data['happened_at'],
                'frequency_type' => 'year',
                'frequency_number' => 1,
            ];

            $reminder = (new AddReminderToLifeEvent)->execute($array);

            $lifeEvent->reminder_id = $reminder->id;
            $lifeEvent->save();
        }
    }
}
