<?php

namespace App\Services\Contact\LifeEvent;

use App\Services\BaseService;
use App\Models\Contact\Reminder;
use App\Models\Contact\LifeEvent;
use App\Exceptions\MissingParameterException;

class DestroyLifeEvent extends BaseService
{
    /**
     * The structure that the method expects to receive as parameter.
     *
     * @var array
     */
    private $structure = [
        'account_id',
        'life_event_id',
    ];

    /**
     * Destroy a life event.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        if (! $this->validateDataStructure($data, $this->structure)) {
            throw new MissingParameterException('Missing parameters');
        }

        $lifeEvent = LifeEvent::where('account_id', $data['account_id'])
            ->findOrFail($data['life_event_id']);

        $this->deleteAssociatedReminder($lifeEvent);

        $lifeEvent->delete();

        return true;
    }

    /**
     * Delete the associated reminder, if it's set.
     */
    private function deleteAssociatedReminder($lifeEvent)
    {
        if ($lifeEvent->reminder_id) {
            Reminder::where('id', $lifeEvent->reminder_id)->delete();
        }
    }
}
