<?php

namespace App\Services\Account\Activity;

use Carbon\Carbon;
use App\Models\Contact\Contact;
use App\Models\Account\ActivityType;

class ActivityStatisticService
{
    /**
     * Return the activities with the contact in a given timeframe.
     *
     * @param Contact $contact
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Support\Collection
     */
    public function activitiesWithContactInTimeRange(Contact $contact, Carbon $startDate, Carbon $endDate)
    {
        return $contact->activities()
                            ->where('date_it_happened', '>=', $startDate)
                            ->where('date_it_happened', '<=', $endDate)
                            ->orderBy('date_it_happened', 'desc')
                            ->get();
    }

    /**
     * Get the list of number of activities per year in total done with
     * the contact.
     *
     * @param  Contact $contact
     * @return \Illuminate\Support\Collection
     */
    public function activitiesPerYearWithContact(Contact $contact)
    {
        return $contact->activityStatistics;
    }

    /**
     * Get the list of activities per month for a given year.
     *
     * @param  Contact $contact
     * @param  int     $year
     * @return \Illuminate\Support\Collection
     */
    public function activitiesPerMonthForYear(Contact $contact, int $year)
    {
        $startDate = Carbon::create($year, 1, 1, 0, 0, 0);
        $endDate = Carbon::create($year, 12, 31);

        $activities = $this->activitiesWithContactInTimeRange($contact, $startDate, $endDate);

        $activitiesPerMonth = collect([]);
        for ($month = 1; $month < 13; $month++) {
            $activitiesInMonth = collect([]);

            foreach ($activities as $activity) {
                if ($activity->date_it_happened->month === $month) {
                    $activitiesInMonth->push($activity);
                }
            }

            $activitiesPerMonth->push([
                'month' => $month,
                'occurences' => $activitiesInMonth->count(),
                'activities' => $activitiesInMonth,
            ]);
        }

        $maxActivitiesInAMonth = $activitiesPerMonth->max('occurences');

        $activitiesPerMonth->transform(function ($activity) use ($maxActivitiesInAMonth) {
            if ($activity['occurences'] != 0) {
                $activity['percent'] = ($activity['occurences'] * 100 / $maxActivitiesInAMonth);
            } else {
                $activity['percent'] = 0;
            }

            return $activity;
        });

        return $activitiesPerMonth;
    }

    /**
     * Get the list of unique activity types for activities done with
     * a contact in a given timeframe, along with the number of occurences.
     *
     * @param Contact $contact
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Support\Collection
     */
    public function uniqueActivityTypesInTimeRange(Contact $contact, Carbon $startDate, Carbon $endDate)
    {
        $activities = $this->activitiesWithContactInTimeRange($contact, $startDate, $endDate);

        // group activities by activity type id
        $grouped = $activities->groupBy(function ($item, $key) {
            return $item['activity_type_id'];
        });

        // remove activity type id that are null
        $grouped = $grouped->reject(function ($value, $key) {
            return $key == '';
        });

        // calculate how many occurences of unique activity type id
        $activities = $grouped->map(function ($item, $key) {
            return collect($item)->count();
        });

        $activityTypes = collect([]);
        foreach ($activities as $key => $value) {
            $activityTypes->push([
                'object' => ActivityType::find($key),
                'occurences' => $value,
            ]);
        }

        return $activityTypes;
    }
}
