<?php

namespace App\Services\Contact\LifeEvent;

use App\Services\BaseService;
use App\Models\Contact\LifeEvent;
use App\Models\Contact\LifeEventType;
use App\Exceptions\MissingParameterException;

class UpdateLifeEvent extends BaseService
{
    /**
     * The structure that the method expects to receive as parameter.
     *
     * @var array
     */
    private $structure = [
        'account_id',
        'life_event_id',
        'life_event_type_id',
        'happened_at',
        'name',
        'note',
    ];

    /**
     * Update a life event.
     *
     * @param array $data
     * @return LifeEvent
     */
    public function execute(array $data) : LifeEvent
    {
        if (! $this->validateDataStructure($data, $this->structure)) {
            throw new MissingParameterException('Missing parameters');
        }

        $lifeEvent = LifeEvent::where('account_id', $data['account_id'])
            ->findOrFail($data['life_event_id']);

        LifeEventType::where('account_id', $data['account_id'])
            ->findOrFail($data['life_event_type_id']);

        $lifeEvent->update([
            'happened_at' => $data['happened_at'],
            'life_event_type_id' => $data['life_event_type_id'],
            'name' => $data['name'],
            'note' => $data['note'],
        ]);

        return $lifeEvent;
    }
}
