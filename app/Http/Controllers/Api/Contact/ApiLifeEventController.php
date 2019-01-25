<?php

namespace App\Http\Controllers\Api\Contact;

use Illuminate\Http\Request;
use App\Models\Contact\LifeEvent;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\LifeEvent\CreateLifeEvent;
use App\Services\Contact\LifeEvent\UpdateLifeEvent;
use App\Services\Contact\LifeEvent\DestroyLifeEvent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\LifeEvent\LifeEvent as LifeEventResource;

class ApiLifeEventController extends ApiController
{
    /**
     * Get the list of life events.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $lifeEvents = auth()->user()->account->lifeEvents()
            ->orderBy($this->sort, $this->sortDirection)
            ->paginate($this->getLimitPerPage());

        return LifeEventResource::collection($lifeEvents);
    }

    /**
     * Get the detail of a given life event.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $lifeEventId)
    {
        try {
            $lifeEvent = LifeEvent::where('account_id', auth()->user()->account_id)
                                    ->findOrFail($lifeEventId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new LifeEventResource($lifeEvent);
    }

    /**
     * Store the life event.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $lifeEvent = (new CreateLifeEvent)->execute(
                $request->all()
                +
                [
                    'account_id' => auth()->user()->account->id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }

        return new LifeEventResource($lifeEvent);
    }

    /**
     * Update the life event.
     *
     * @param  Request $request
     * @param  int $lifeEventId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $lifeEventId)
    {
        try {
            $lifeEvent = (new UpdateLifeEvent)->execute(
                $request->all()
                    +
                    [
                    'account_id' => auth()->user()->account->id,
                    'life_event_id' => $lifeEventId,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }

        return new LifeEventResource($lifeEvent);
    }

    /**
     * Destroy the life event.
     *
     * @param  Request $request
     * @param  int $lifeEventId
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $lifeEventId)
    {
        try {
            (new DestroyLifeEvent)->execute([
                'account_id' => auth()->user()->account->id,
                'life_event_id' => $lifeEventId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return $this->respondObjectDeleted((int) $lifeEventId);
    }
}
