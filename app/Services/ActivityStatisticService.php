<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Contact\ActivityType;
use App\Models\Contact\ActivityStatistic;
use App\Models\Contact\Contact;

class ActivityStatisticService
{
    /**
     * Return the activities with the contact in a given timeframe.
     *
     * @param Contact $contact
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    public function activitiesWithContactInTimeframe(Contact $contact, Carbon $startDate, Carbon $endDate)
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
     * @return Collection
     */
    public function activitiesPerYearWithContact(Contact $contact)
    {
        return $contact->activityStatistics;
    }

    /**
     * Get the list of unique activity types for activities done with
     * a contact in a given timeframe, along with the number of occurences.
     *
     * @param Contact $contact
     * @param integer $year
     * @return Collection
     */
    public function uniqueActivityTypesInTimeframe(Contact $contact, Carbon $startDate, Carbon $endDate)
    {
        $activities = $this->activitiesWithContactInTimeframe($contact, $startDate, $endDate);

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
