<?php

namespace App\Http\Controllers\Contacts;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\AccountHelper;
use App\Models\Contact\Contact;
use App\Models\Account\Activity;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Models\Account\ActivityType;
use App\Traits\JsonRespondController;
use App\Services\Account\Activity\Activity\CreateActivity;
use App\Services\Account\Activity\Activity\UpdateActivity;
use App\Services\Account\Activity\Activity\DestroyActivity;
use App\Services\Account\Activity\ActivityStatisticService;
use App\Http\Resources\Activity\Activity as ActivityResource;

class ActivitiesController extends Controller
{
    use JsonRespondController;

    /**
     * Get the list of activities.
     *
     * @param  Request  $request
     * @param  Contact  $contact
     * @return \Illuminate\Http\Resources\Json\ResourceCollection
     */
    public function index(Request $request, Contact $contact)
    {
        $activities = $contact->activities()
                            ->orderBy('happened_at', 'desc')
                            ->limit(10)
                            ->get();

        return ActivityResource::collection($activities)->additional(['meta' => [
            'statistics' => AccountHelper::getYearlyActivitiesStatistics($contact->account),
        ]]);
    }

    /**
     * Get the list of contacts available to associate the activity with
     * participants.
     * We could have chosen to query `/people` to get the full list of contacts
     * but some accounts have thousands of contacts. Thus for performance
     * purposes we have to create our own collection containing just the
     * necessary information.
     * Also we need to filter out the current contact from the list.
     *
     * @param  Request  $request
     * @param  Contact  $contact
     * @return Collection
     */
    public function contacts(Request $request, Contact $contact)
    {
        return auth()->user()->account->contacts
            ->filter(function ($c) use ($contact) {
                return $contact->id !== $c->id;
            })
            ->map(function (Contact $c): array {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                ];
            });
    }

    /**
     * Get the list of activity categories.
     *
     * @param  Request  $request
     * @return Collection
     */
    public function categories(Request $request)
    {
        $categories = auth()->user()->account->activityTypeCategories;

        $array = collect([]);
        foreach ($categories as $category) {
            $types = ActivityType::where('activity_type_category_id', $category->id)->get();

            $typeCollection = collect([]);
            foreach ($types as $type) {
                $typeCollection->push([
                    'id' => $type->id,
                    'name' => $type->name,
                ]);
            }

            $array->push([
                'id' => $category->id,
                'name' => $category->name,
                'types' => $typeCollection,
            ]);
        }

        return $array;
    }

    /**
     * Display the summary of activities for a given contact.
     *
     * @param  Request  $request
     * @param  Contact  $contact
     * @return \Illuminate\Http\RedirectResponse
     */
    public function summary(Request $request, Contact $contact)
    {
        // get the year of the most recent activity done with the contact
        $year = $contact->activities->sortByDesc('happened_at')
                        ->first()
                        ->happened_at
                        ->year;

        return redirect()->route('people.activities.year', [$contact, $year]);
    }

    /**
     * Get all the activities for this contact for a specific year.
     */
    public function year(ActivityStatisticService $activityStatisticService, Contact $contact, int $year)
    {
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);

        $activitiesLastTwelveMonths = $activityStatisticService
                        ->activitiesWithContactInTimeRange($contact, now()->subMonths(12), now())
                        ->count();

        $uniqueActivityTypes = $activityStatisticService
                        ->uniqueActivityTypesInTimeRange($contact, $startDate, $endDate);

        $activitiesPerYear = $activityStatisticService->activitiesPerYearWithContact($contact);

        $activitiesPerMonthForYear = $activityStatisticService
                        ->activitiesPerMonthForYear($contact, $year)
                        ->sortByDesc('month');

        return view('people.activities.year')
            ->withTotalActivities($contact->activities->count())
            ->withActivitiesLastTwelveMonths($activitiesLastTwelveMonths)
            ->withUniqueActivityTypes($uniqueActivityTypes)
            ->withActivitiesPerYear($activitiesPerYear)
            ->withActivitiesPerMonthForYear($activitiesPerMonthForYear)
            ->withYear($year)
            ->withContact($contact);
    }

    /**
     * Store the activity.
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\Support\Responsable
     */
    public function store(Request $request)
    {
        $activity = app(CreateActivity::class)->execute(
            $request->except(['account_id'])
                +
                [
                    'account_id' => auth()->user()->account_id,
                ]
        );

        return new ActivityResource($activity);
    }

    /**
     * Update the activity.
     *
     * @param  Request  $request
     * @param  Activity  $activity
     * @return \Illuminate\Contracts\Support\Responsable
     */
    public function update(Request $request, Activity $activity)
    {
        $activity = app(UpdateActivity::class)->execute(
            $request->except(['account_id', 'activity_id'])
                +
                [
                    'account_id' => auth()->user()->account_id,
                    'activity_id' => $activity->id,
                ]
        );

        return new ActivityResource($activity);
    }

    /**
     * Delete an activity.
     *
     * @param  Request  $request
     * @param  Activity  $activity
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Activity $activity)
    {
        app(DestroyActivity::class)->execute([
            'account_id' => auth()->user()->account_id,
            'activity_id' => $activity->id,
        ]);

        return $this->respondObjectDeleted($activity->id);
    }
}
