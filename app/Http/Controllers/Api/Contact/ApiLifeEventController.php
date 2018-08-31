<?php

namespace App\Http\Controllers\Api\Contact;

use Illuminate\Http\Request;
use App\Models\Contact\Conversation;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Conversation\CreateConversation;
use App\Services\Contact\Conversation\UpdateConversation;
use App\Services\Contact\Conversation\DestroyConversation;
use App\Http\Resources\Conversation\Conversation as LifeEventResource;

class ApiLifeEventController extends ApiController
{
    /**
     * Get the list of life events.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $lifeEvents = auth()->user()->account->conversations()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

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
            $lifeEvent = Conversation::where('account_id', auth()->user()->account_id)
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
            $lifeEvent = (new CreateConversation)->execute(
                $request->all()
                    +
                    [
                    'account_id' => auth()->user()->account->id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (\Exception $e) {
            return $this->setHTTPStatusCode(500)
                ->setErrorCode(41)
                ->respondWithError(config('api.error_codes.41'));
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
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
            $lifeEvent = (new UpdateConversation)->execute(
                $request->all()
                    +
                    [
                    'account_id' => auth()->user()->account->id,
                    'conversation_id' => $lifeEventId,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (\Exception $e) {
            return $this->setHTTPStatusCode(500)
                ->setErrorCode(41)
                ->respondWithError(config('api.error_codes.41'));
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
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
            (new DestroyConversation)->execute([
                'account_id' => auth()->user()->account->id,
                'conversation_id' => $lifeEventId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (\Exception $e) {
            return $this->setHTTPStatusCode(500)
                ->setErrorCode(41)
                ->respondWithError(config('api.error_codes.41'));
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respondObjectDeleted((int) $lifeEventId);
    }
}
