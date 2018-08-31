<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact\LifeEvent;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Contact\LifeEvent;
use App\Models\Contact\LifeEventType;
use Illuminate\Database\QueryException;
use App\Models\Contact\ContactFieldType;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
    ];

    /**
     * Create a life event.
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
            Contact::where('account_id', $data['account_id'])
                ->findOrFail($data['contact_id']);
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('Can not find the contact in this account.');
        }

        try {
            LifeEventType::where('account_id', $data['account_id'])
                ->findOrFail($data['life_event_type_id']);
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('Can not find the life event type.');
        }

        try {
            $lifeEvent = LifeEvent::create($data);
        } catch (QueryException $e) {
            throw new QueryException('Can not create the life event.');
        }

        return $lifeEvent;
    }
}
