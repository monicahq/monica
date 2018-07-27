<?php

namespace App\Http\Controllers\Contacts;

use Carbon\Carbon;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Services\ActivityStatisticService;

class ActivitiesController extends Controller
{
    /**
     * Statistics about an activity
     *
     * @var ActivityStatisticService
     */
    protected $activityStatisticService;

    public function __construct(ActivityStatisticService $service)
    {
        $this->activityStatisticService = $service;
    }

    /**
     * Get all the activities for this contact.
     */
    public function index(Contact $contact)
    {
        $activitiesLastTwelveMonths = $this->activityStatisticService
                        ->activitiesWithContactInTimeframe($contact, Carbon::now()->subMonths(12), Carbon::now())
                        ->count();

        $uniqueActivityTypes = $this->activityStatisticService
                        ->uniqueActivityTypesInTimeframe($contact, Carbon::now()->startOfYear(), Carbon::now());

        $activitiesPerYear = $this->activityStatisticService->activitiesPerYearWithContact($contact);
dd($activitiesPerYear);
        return view('people.activities.index')
            ->withTotalActivities($contact->activities->count())
            ->withActivitiesLastTwelveMonths($activitiesLastTwelveMonths)
            ->withUniqueActivityTypes($uniqueActivityTypes)
            ->withActivitiesPerYear($activitiesPerYear)
            ->withContact($contact);
    }
}
