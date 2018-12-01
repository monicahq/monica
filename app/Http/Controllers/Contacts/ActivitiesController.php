<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



namespace App\Http\Controllers\Contacts;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Services\ActivityStatisticService;

class ActivitiesController extends Controller
{
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
     * Get all the activities for this contact.
     */
    public function index(Contact $contact)
    {
        // get the year of the most recent activity done with the contact
        $year = $contact->activities->sortByDesc('date_it_happened')->first()->date_it_happened->year;

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
                        ->activitiesWithContactInTimeRange($contact, Carbon::now()->subMonths(12), Carbon::now())
                        ->count();

        $uniqueActivityTypes = $this->activityStatisticService
                        ->uniqueActivityTypesInTimeRange($contact, $startDate, $endDate);

        $activitiesPerYear = $this->activityStatisticService->activitiesPerYearWithContact($contact);

        $activitiesPerMonthForYear = $this->activityStatisticService
                        ->activitiesPerMonthForYear($contact, $year)
                        ->sortByDesc('month');

        return view('people.activities.index')
            ->withTotalActivities($contact->activities->count())
            ->withActivitiesLastTwelveMonths($activitiesLastTwelveMonths)
            ->withUniqueActivityTypes($uniqueActivityTypes)
            ->withActivitiesPerYear($activitiesPerYear)
            ->withActivitiesPerMonthForYear($activitiesPerMonthForYear)
            ->withYear($year)
            ->withContact($contact);
    }
}
