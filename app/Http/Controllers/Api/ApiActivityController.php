<?php

namespace App\Http\Controllers\Api;

use Validator;
use App\Contact;
use App\Activity;
use App\ActivityType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Activity\Activity as ActivityResource;
use App\Http\Resources\Activity\ActivityType as ActivityTypeResource;

class ApiActivityController extends ApiController
{
    /**
     * Get the list of activities.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $activities = auth()->user()->account->activities()
                                ->paginate($this->getLimitPerPage());

        return ActivityResource::collection($activities);
    }

    /**
     * Get the detail of a given activity
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $activityId)
    {
        try {
            $activity = Activity::where('account_id', auth()->user()->account_id)
                ->where('id', $activityId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new ActivityResource($activity);
    }

    /**
     * Store the activity
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'summary' => 'required|max:100000',
            'description' => 'required|max:1000000',
            'date_it_happened' => 'required|date',
            'activity_type_id' => 'integer',
            'contact_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
           return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        try {
            $activity = Activity::create($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        $activity->account_id = auth()->user()->account->id;
        $activity->save();

        return new ActivityResource($activity);
    }

    /**
     * Update the activity
     * @param  Request $request
     * @param  Integer $activityId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $activityId)
    {
        try {
            $activity = Activity::where('account_id', auth()->user()->account_id)
                ->where('id', $activityId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'summary' => 'required|max:100000',
            'description' => 'required|max:1000000',
            'date_it_happened' => 'required|date',
            'activity_type_id' => 'integer',
            'contact_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
           return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        try {
            $activity->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new ActivityResource($activity);
    }

    /**
     * Delete a note
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $activityId)
    {
        try {
            $activity = Note::where('account_id', auth()->user()->account_id)
                ->where('id', $activityId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $activity->delete();

        return $this->respondObjectDeleted($activity->id);
    }

    /**
     * Get the list of activities for the given contact.
     *
     * @return \Illuminate\Http\Response
     */
    public function activities(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $activities = $contact->activities()
                ->paginate($this->getLimitPerPage());

        return ActivityResource::collection($activities);
    }

    /**
     * Get the list of all activity types.
     *
     * @return \Illuminate\Http\Response
     */
    public function activitytypes(Request $request)
    {
        $activities = ActivityType::all();

        return ActivityTypeResource::collection($activities);
    }
}
