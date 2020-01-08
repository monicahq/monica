<?php

namespace App\Http\Controllers\Contacts;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Account\Activity;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Models\Account\ActivityType;
use App\Traits\JsonRespondController;
use App\Services\Account\Activity\ActivityStatisticService;
use App\Http\Resources\Activity\Activity as ActivityResource;

class ActivitiesController extends Controller
{
    use JsonRespondController;

    /**
     * Statistics about an activity.
     *
     * @var ActivityStatisticService
     */
    protected $activityStatisticService;

    public function __construct(ActivityStatisticService $service)
    {
        $this->activityStatisticService = $service;
    }

    /**
     * Get the list of activities.
     *
     * @param Request $request
     * @param Contact $contact
     * @return \Illuminate\Http\Resources\Json\ResourceCollection
     */
    public function index(Request $request, Contact $contact)
    {
        $activities = $contact->activities()
                            ->orderBy('happened_at', 'desc')
                            ->limit(10)
                            ->get();

        return ActivityResource::collection($activities);
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
     * @param Request $request
     * @param Contact $contact
     * @return Collection
     */
    public function contacts(Request $request, Contact $contact)
    {
        return auth()->user()->account->contacts
            ->filter(function ($c) use ($contact) {
                return $contact->id !== $c->id;
            })
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                ];
            });
    }

    /**
     * Get the list of activity categories.
     *
     * @param Request $request
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
     * @param Request $request
     * @param Contact $contact
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
    public function year(Request $request, Contact $contact, int $year)
    {
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);

        $activitiesLastTwelveMonths = $this->activityStatisticService
                        ->activitiesWithContactInTimeRange($contact, now()->subMonths(12), now())
                        ->count();

        $uniqueActivityTypes = $this->activityStatisticService
                        ->uniqueActivityTypesInTimeRange($contact, $startDate, $endDate);

        $activitiesPerYear = $this->activityStatisticService->activitiesPerYearWithContact($contact);

        $activitiesPerMonthForYear = $this->activityStatisticService
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
}
