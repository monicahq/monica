<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact\LifeEvent;

use App\Services\BaseService;
use App\Models\Contact\LifeEvent;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
            $lifeEvent->delete();
        } catch (QueryException $e) {
            throw new QueryException('Can not delete the life event.');
        }

        return true;
    }
}
