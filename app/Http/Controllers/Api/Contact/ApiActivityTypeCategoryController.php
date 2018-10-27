<?php

namespace App\Http\Controllers\Api\Contact;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\ApiController;
use App\Models\Contact\ActivityTypeCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Activity\ActivityTypeCategory as ActivityTypeCategoryResource;

class ApiActivityTypeCategoryController extends ApiController
{
    /**
     * Get the list of activity type categories.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $activityTypeCategories = auth()->user()->account->activityTypeCategories()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return ActivityTypeCategoryResource::collection($activityTypeCategories);
    }

    /**
     * Get the detail of a given activity type category.
     *
     * @param  Request $request
     * @param  int $activityTypeCategoryId
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $activityTypeCategoryId)
    {
        try {
            $activityTypeCategory = ActivityTypeCategory::where('account_id', auth()->user()->account_id)
                ->where('id', $activityTypeCategoryId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new ActivityTypeCategoryResource($activityTypeCategory);
    }

    /**
     * Store the activity type category.
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
            $activityTypeCategory = ActivityTypeCategory::create(
                $request->all()
                + ['account_id' => auth()->user()->account_id]
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new ActivityTypeCategoryResource($activityTypeCategory);
    }

    /**
     * Update the activity type category.
     *
     * @param  Request $request
     * @param  int $activityTypeCategoryId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $activityTypeCategoryId)
    {
        try {
            $activityTypeCategory = ActivityTypeCategory::where('account_id', auth()->user()->account_id)
                ->where('id', $activityTypeCategoryId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $isValid = $this->validateRequest($request);
        if ($isValid !== true) {
            return $isValid;
        }

        try {
            $activityTypeCategory->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new ActivityTypeCategoryResource($activityTypeCategory);
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
            'name' => 'required|max:255|string',
        ]);

        if ($validator->fails()) {
            return $this->setHTTPStatusCode(400)
                        ->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        return true;
    }

    /**
     * Delete an activity type category.
     * When an activity type category is deleted, all the activity types that
     * belong to it are also deleted.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $activityTypeCategoryId)
    {
        try {
            $activityTypeCategory = ActivityTypeCategory::where('account_id', auth()->user()->account_id)
                ->where('id', $activityTypeCategoryId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $activityTypeCategory->deleteAllAssociatedActivityTypes();
        $activityTypeCategory->delete();

        return $this->respondObjectDeleted($activityTypeCategory->id);
    }
}
