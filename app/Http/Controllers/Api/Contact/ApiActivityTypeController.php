<?php

namespace App\Http\Controllers\Api;

use App\Models\Contact\ActivityType;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Activity\ActivityType as ActivityTypeResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiActivityTypeController extends ApiController
{
    /**
     * Get the list of activity types.
     *
     * @return \Illuminate\Http\Response
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
     * @param  Request $request
     * @return \Illuminate\Http\Response
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
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $isValid = $this->validateRequest($request);
        if ($isValid !== true) {
            return $isValid;
        }

        try {
            $activityType = ActivityType::create($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        $activityType->account_id = auth()->user()->account->id;
        $activityType->save();

        return new ActivityTypeResource($activityType);
    }

    /**
     * Update the activity type.
     * 
     * @param  Request $request
     * @param  int $activityTypeId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $activityTypeId)
    {
        try {
            $activityType = ActivityType::where('account_id', auth()->user()->account_id)
                ->where('id', $activityTypeId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $isValid = $this->validateRequest($request);
        if ($isValid !== true) {
            return $isValid;
        }

        try {
            $activityType->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new ActivityTypeResource($activityType);
    }

    /**
     * Validate the request for store/update.
     *
     * @param  Request $request
     * @return mixed
     */
    private function validateRequest(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'content' => 'required|max:100000',
            'called_at' => 'required|date',
            'contact_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return true;
    }

    /**
     * Delete an activity type.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $activityTypeId)
    {
        try {
            $activityType = ActivityType::where('account_id', auth()->user()->account_id)
                ->where('id', $activityTypeId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $activityType->delete();

        return $this->respondObjectDeleted($activityType->id);
    }
}
