<?php

namespace App\Http\Controllers\Api\Account\Activity;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Account\Activity;
use App\Models\Journal\JournalEntry;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use App\Services\Activity\Activity\CreateActivity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Activity\Activity as ActivityResource;

class ApiActivityController extends ApiController
{
    /**
     * Get the list of activities.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
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
     *
     * @param Request $request
     *
     * @return ActivityResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $activityId)
    {
        try {
            $activity = Activity::where('account_id', auth()->user()->account_id)
                ->findOrFail($activityId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new ActivityResource($activity);
    }

    /**
     * Store the activity.
     *
     * @param Request $request
     *
     * @return ActivityResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $activity = app(CreateActivity::class)->execute(
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
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ActivityResource($activityType);

        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $activity = Activity::create(
                $request->only([
                    'summary',
                    'date_it_happened',
                    'activity_type_id',
                    'description',
                ])
                + ['account_id' => auth()->user()->account_id]
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        // Log a journal entry
        (new JournalEntry)->add($activity);

        // Now we associate the activity with each one of the attendees
        $attendeesID = $request->get('contacts');
        foreach ($attendeesID as $attendeeID) {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->findOrFail($attendeeID);
            $contact->activities()->attach($activity, [
                    'account_id' => auth()->user()->account_id,
                ]);
            $contact->calculateActivitiesStatistics();
        }

        return new ActivityResource($activity);
    }

    /**
     * Update the activity.
     *
     * @param Request $request
     * @param int $activityId
     *
     * @return ActivityResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $activityId)
    {
        try {
            $activity = Activity::where('account_id', auth()->user()->account_id)
                ->findOrFail($activityId);
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
        $attendeesID = $request->get('contacts');

        // Find existing contacts
        $existing = $activity->contacts()->get();

        foreach ($existing as $contact) {
            // Has an existing attendee been removed?
            if (! in_array($contact->id, $attendeesID)) {
                $contact->activities()->detach($activity);
            }

            // Remove this ID from our list of contacts as we don't
            // want to add them to the activity again
            $idx = array_search($contact->id, $attendeesID);
            unset($attendeesID[$idx]);

            $contact->calculateActivitiesStatistics();
        }

        // New attendees
        foreach ($attendeesID as $attendeeID) {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->findOrFail($attendeeID);
            $contact->activities()->attach($activity, [
                'account_id' => auth()->user()->account_id,
            ]);
        }

        return new ActivityResource($activity);
    }

    /**
     * Delete an activity.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $activityId)
    {
        try {
            $activity = Activity::where('account_id', auth()->user()->account_id)
                ->findOrFail($activityId);
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
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function activities(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->findOrFail($contactId);
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
}
