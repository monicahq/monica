<?php

namespace App\Http\Controllers\Api\Account;

use Illuminate\Http\Request;
use App\Models\Account\Place;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use App\Services\Account\Place\CreatePlace;
use App\Services\Account\Place\UpdatePlace;
use App\Services\Account\Place\DestroyPlace;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Place\Place as PlaceResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiPlaceController extends ApiController
{
    /**
     * Get the list of places.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
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
     * @param  Request  $request
     * @return PlaceResource|\Illuminate\Http\JsonResponse
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
     * @param  Request  $request
     * @return PlaceResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $place = app(CreatePlace::class)->execute(
                $request->except(['account_id'])
                    +
                    [
                        'account_id' => auth()->user()->account_id,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new PlaceResource($place);
    }

    /**
     * Update a place.
     *
     * @param  Request  $request
     * @param  int  $placeId
     * @return PlaceResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $placeId)
    {
        try {
            $place = app(UpdatePlace::class)->execute(
                $request->except(['account_id', 'place_id'])
                    +
                    [
                        'account_id' => auth()->user()->account_id,
                        'place_id' => $placeId,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new PlaceResource($place);
    }

    /**
     * Delete a place.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, int $placeId)
    {
        try {
            app(DestroyPlace::class)->execute([
                'account_id' => auth()->user()->account_id,
                'place_id' => $placeId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respondObjectDeleted($placeId);
    }
}
