<?php

namespace App\Http\Controllers\Api;

use App\Note;
use App\Contact;
use App\Activity;
use App\ActivityType;
use App\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
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
        try {
            $activities = auth()->user()->account->activities()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return ActivityResource::collection($activities)->additional(['meta' => [
            'statistics' => auth()->user()->account->getYearlyActivitiesStatistics(),
        ]]);
    }

    /**
     * Get the detail of a given activity.
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
     * Store the activity.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $contact = $this->validateUpdate($request);
        if (! $contact instanceof Contact) {
            return $contact;
        }

        try {
            $activity = Activity::create(
                $request->only([
                    'summary',
                    'date_it_happened',
                    'activity_type_id',
                    'description',
                ])
                + ['account_id' => auth()->user()->account->id]
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        // Log a journal entry
        (new JournalEntry)->add($activity);

        // Now we associate the activity with each one of the attendees
        $attendeesID = $request->get('contacts');
        foreach ($attendeesID as $attendeeID) {
            $contact = Contact::findOrFail($attendeeID);
            $contact->activities()->save($activity);
            $contact->logEvent('activity', $activity->id, 'create');
            $contact->calculateActivitiesStatistics();
        }

        return new ActivityResource($activity);
    }

    /**
     * Update the activity.
     * @param  Request $request
     * @param  int $activityId
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

        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        // Update the activity itself
        try {
            $activity->update(
                $request->only([
                    'summary',
                    'date_it_happened',
                    'activity_type_id',
                    'description',
                ])
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        // Log a journal entry but need to delete the previous one first
        $activity->deleteJournalEntry();
        (new JournalEntry)->add($activity);

        // Get the attendees
        $attendees = $request->get('contacts');

        // Find existing contacts
        $existing = $activity->contacts()->get();

        foreach ($existing as $contact) {
            // Has an existing attendee been removed?
            if (! in_array($contact->id, $attendees)) {
                $contact->activities()->detach($activity);
                $contact->logEvent('activity', $activity->id, 'delete');
            } else {
                // Otherwise we're updating an activity that someone's
                // already a part of
                $contact->logEvent('activity', $activity->id, 'update');
            }

            // Remove this ID from our list of contacts as we don't
            // want to add them to the activity again
            $idx = array_search($contact->id, $attendees);
            unset($attendees[$idx]);

            $contact->calculateActivitiesStatistics();
        }

        // New attendees
        foreach ($attendees as $newContactId) {
            $contact = Contact::findOrFail($newContactId);
            $contact->activities()->save($activity);
            $contact->logEvent('activity', $activity->id, 'create');
        }

        return new ActivityResource($activity);
    }

    /**
     * Validate the request for update.
     *
     * @param  Request $request
     * @return mixed
     */
    private function validateUpdate(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'summary' => 'required|max:100000',
            'description' => 'required|max:1000000',
            'date_it_happened' => 'required|date',
            'activity_type_id' => 'integer',
            'contacts' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                ->respondWithError($validator->errors()->all());
        }

        // Make sure each contact exists and has the right to be associated with
        // this account
        $attendeesID = $request->get('contacts');
        foreach ($attendeesID as $attendeeID) {
            try {
                $contact = Contact::where('account_id', auth()->user()->account_id)
                    ->where('id', $attendeeID)
                    ->firstOrFail();
            } catch (ModelNotFoundException $e) {
                return $this->respondNotFound();
            }
        }

        return true;
    }

    /**
     * Delete an activity.
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

        $activity->deleteJournalEntry();

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

        try {
            $activities = $contact->activities()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return ActivityResource::collection($activities)->additional(['meta' => [
            'statistics' => auth()->user()->account->getYearlyActivitiesStatistics(),
        ]]);
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
