<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Account\Activity;
use App\Models\Account\ActivityType;
use App\Models\Journal\JournalEntry;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Activity\Activity as ActivityResource;
use App\Services\Account\Activity\Activity\AttachContactToActivity;
use App\Services\Account\Activity\Activity\CreateActivity;
use App\Services\Account\Activity\Activity\DestroyActivity;
use App\Services\Account\Activity\Activity\UpdateActivity;
use Illuminate\Validation\ValidationException;

class ApiActivitiesController extends ApiController
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
                $request->except(['account_id'])
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

        // Now we associate the activity with each one of the attendees
        try {
            $activity = app(AttachContactToActivity::class)->execute([
                'account_id' => auth()->user()->account->id,
                'activity_id' => $activity->id,
                'contacts' => $request->input('contacts'),
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
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
            $activity = app(UpdateActivity::class)->execute(
                $request->except(['account_id', 'activity_id'])
                    +
                    [
                        'account_id' => auth()->user()->account->id,
                        'activity_id' => $activityId,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        // Now we associate the activity with each one of the attendees
        try {
            $activity = app(AttachContactToActivity::class)->execute([
                'account_id' => auth()->user()->account->id,
                'activity_id' => $activity->id,
                'contacts' => $request->input('contacts'),
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
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
            app(DestroyActivity::class)->execute([
                'account_id' => auth()->user()->account_id,
                'activity_id' => $activityId
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return $this->respondObjectDeleted($activityId);
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
