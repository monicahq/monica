<?php

namespace App\Http\Controllers\Api\Account\Activity;

use Illuminate\Http\Request;
use App\Models\Account\ActivityType;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Account\Activity\ActivityType\CreateActivityType;
use App\Services\Account\Activity\ActivityType\UpdateActivityType;
use App\Services\Account\Activity\ActivityType\DestroyActivityType;
use App\Http\Resources\Activity\ActivityType as ActivityTypeResource;

class ApiActivityTypeController extends ApiController
{
    /**
     * Get the list of activity types.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $activityTypes = auth()->user()->account->activityTypes()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return ActivityTypeResource::collection($activityTypes);
    }

    /**
     * Get the detail of a given activity type.
     *
     * @param  Request  $request
     * @return ActivityTypeResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $activityTypeId)
    {
        try {
            $activityType = ActivityType::where('account_id', auth()->user()->account_id)
                ->where('id', $activityTypeId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new ActivityTypeResource($activityType);
    }

    /**
     * Store the activity type.
     *
     * @param  Request  $request
     * @return ActivityTypeResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $activityType = app(CreateActivityType::class)->execute(
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

        return new ActivityTypeResource($activityType);
    }

    /**
     * Update the activity type.
     *
     * @param  Request  $request
     * @param  int  $activityTypeId
     * @return ActivityTypeResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $activityTypeId)
    {
        try {
            $activityType = app(UpdateActivityType::class)->execute(
                $request->except(['account_id', 'activity_type_id'])
                    +
                    [
                        'account_id' => auth()->user()->account_id,
                        'activity_type_id' => $activityTypeId,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ActivityTypeResource($activityType);
    }

    /**
     * Delete an activity type.
     *
     * @param  Request  $request
     * @param  int  $activityTypeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, int $activityTypeId)
    {
        try {
            app(DestroyActivityType::class)->execute([
                'account_id' => auth()->user()->account_id,
                'activity_type_id' => $activityTypeId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respondObjectDeleted($activityTypeId);
    }
}
