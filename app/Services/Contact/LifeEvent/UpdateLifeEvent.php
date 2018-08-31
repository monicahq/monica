<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact\LifeEvent;

use App\Services\BaseService;
use App\Models\Contact\Conversation;
use Illuminate\Database\QueryException;
use App\Models\Contact\LifeEvent;
use App\Models\Contact\LifeEventType;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
        if (!$this->validateDataStructure($data, $this->structure)) {
            throw new \Exception('Missing parameters');
        }

        try {
            $lifeEvent = LifeEvent::where('account_id', $data['account_id'])
                ->findOrFail($data['life_event_id']);
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('Can not find the life event in this account.');
        }

        try {
            LifeEventType::where('account_id', $data['account_id'])
                ->findOrFail($data['life_event_type_id']);
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('Can not find the life event type.');
        }

        try {
            $lifeEvent->update([
                'happened_at' => $data['happened_at'],
                'life_event_type_id' => $data['life_event_type_id'],
                'name' => $data['name'],
                'note' => $data['note'],
            ]);
        } catch (QueryException $e) {
            throw new QueryException('Can not update the life event.');
        }

        return $lifeEvent;
    }
}
