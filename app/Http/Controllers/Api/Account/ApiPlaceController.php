<?php

namespace App\Http\Controllers\Api\Account;

use App\Models\Account\Place;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use Illuminate\Database\QueryException;
use App\Services\Account\Place\CreatePlace;
use App\Services\Account\Place\UpdatePlace;
use App\Services\Account\Place\DestroyPlace;
use App\Http\Controllers\Api\ApiController;
use App\Exceptions\MissingParameterException;
use App\Http\Resources\Place\Place as PlaceResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiPlaceController extends ApiController
{
    /**
     * Get the list of places.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $places = auth()->user()->account->places()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return PlaceResource::collection($places);
    }

    /**
     * Get the detail of a given place.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $placeId)
    {
        try {
            $place = Place::where('account_id', auth()->user()->account_id)
                ->where('id', $placeId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new PlaceResource($place);
    }

    /**
     * Store the place.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $place = (new CreatePlace)->execute(
                $request->all()
                    +
                    [
                    'account_id' => auth()->user()->account->id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (MissingParameterException $e) {
            return $this->respondInvalidParameters($e->errors);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new PlaceResource($place);
    }

    /**
     * Update a place.
     *
     * @param  Request $request
     * @param  int $placeId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $placeId)
    {
        try {
            $place = (new UpdatePlace)->execute(
                $request->all()
                    +
                    [
                    'account_id' => auth()->user()->account->id,
                    'place_id' => $placeId,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (MissingParameterException $e) {
            return $this->respondInvalidParameters($e->errors);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new PlaceResource($place);
    }

    // /**
    //  * Delete a call.
    //  *
    //  * @param  Request $request
    //  * @return \Illuminate\Http\Response
    //  */
    // public function destroy(Request $request, $placeId)
    // {
    //     try {
    //         (new DestroyCall)->execute([
    //             'account_id' => auth()->user()->account->id,
    //             'call_id' => $placeId,
    //         ]);
    //     } catch (ModelNotFoundException $e) {
    //         return $this->respondNotFound();
    //     } catch (MissingParameterException $e) {
    //         return $this->respondInvalidParameters($e->errors);
    //     } catch (QueryException $e) {
    //         return $this->respondInvalidQuery();
    //     }

    //     return $this->respondObjectDeleted((int)$placeId);
    // }

    // /**
    //  * Get the list of calls for a given contact.
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function calls(Request $request, $contactId)
    // {
    //     try {
    //         $contact = Contact::where('account_id', auth()->user()->account_id)
    //             ->where('id', $contactId)
    //             ->firstOrFail();
    //     } catch (ModelNotFoundException $e) {
    //         return $this->respondNotFound();
    //     }

    //     $places = $contact->calls()
    //         ->orderBy($this->sort, $this->sortDirection)
    //         ->paginate($this->getLimitPerPage());

    //     return PlaceResource::collection($places)->additional(['meta' => [
    //         'statistics' => auth()->user()->account->getYearlyCallStatistics(),
    //     ]]);
    // }
}
