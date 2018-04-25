<?php

namespace App\Http\Controllers\Api\Statistics;

use Exception;
use Illuminate\Http\Request;

class ApiStatisticsController extends ApiController
{
    /**
     * Get the list of general, public statistics.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (! config('monica.allow_statistics_through_public_api_access')) {
            throw new Exception(trans('people.stay_in_touch_invalid'));
        }

        // Collecting statistics
        $statistic = \App\Statistic::order_by('created_at', 'desc')->first();
        $instance = Instance::first();

        // Get the date of the monday of last week
        $dateMondayLastWeek = \Carbon\Carbon::now()->subDays(7);
        $dateMondayLastWeek = $dateMondayLastWeek->startOfWeek();

        // Get the date of the sunday of last week
        $dateSundayLastWeek = \Carbon\Carbon::now()->subDays(7);
        $dateSundayLastWeek = $dateSundayLastWeek->endOfWeek();

        // Get the number of users last monday
        $instanceLastMonday = \App\Statistic::whereDate('created_at', '=', $dateMondayLastWeek->format('Y-m-d'))->first();
        $instanceLastSunday = \App\Statistic::whereDate('created_at', '=', $dateSundayLastWeek->format('Y-m-d'))->first();

        $statistics = collect();
        $statistics->push([
            'instance_creation_date' => $instance->created_at,
            'number_of_contacts' => $statistic->number_of_contacts,
            'number_of_users' => $statistic->number_of_users,
            'number_of_activities' => $statistic->number_of_activities,
            'number_of_reminders' => $statistic->number_of_reminders,
            'number_of_new_users_last_week' => ,
        ]);
    }
}
