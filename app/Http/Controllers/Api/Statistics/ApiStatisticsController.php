<?php

namespace App\Http\Controllers\Api\Statistics;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;

class ApiStatisticsController extends ApiController
{
    /**
     * Get the list of general, public statistics.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (config('monica.allow_statistics_through_public_api_access') == false) {
            return $this->respondNotFound();
        }

        // Collecting statistics
        $statistic = \App\Statistic::orderBy('created_at', 'desc')->first();
        $instance = \App\Instance::first();

        // Get the date of the monday of last week
        $dateMondayLastWeek = \Carbon\Carbon::now()->subDays(7);
        $dateMondayLastWeek = $dateMondayLastWeek->startOfWeek();

        // Get the date of the sunday of last week
        $dateSundayLastWeek = \Carbon\Carbon::now()->subDays(7);
        $dateSundayLastWeek = $dateSundayLastWeek->endOfWeek();

        // Get the number of users last monday
        $instanceLastMonday = \App\Statistic::whereDate('created_at', '=', $dateMondayLastWeek->format('Y-m-d'))->first();
        $instanceLastSunday = \App\Statistic::whereDate('created_at', '=', $dateSundayLastWeek->format('Y-m-d'))->first();

        $numberNewUsers = 0;
        if ($instanceLastMonday && $instanceLastSunday) {
            $numberNewUsers = $instanceLastSunday->number_of_users - $instanceLastMonday->number_of_users;
        }

        $statistics = collect();
        $statistics->push([
            'instance_creation_date' => $instance->created_at->format(config('api.timestamp_format')),
            'number_of_contacts' => ($statistic ? $statistic->number_of_contacts : 0),
            'number_of_users' => ($statistic ? $statistic->number_of_users : 0),
            'number_of_activities' => ($statistic ? $statistic->number_of_activities : 0),
            'number_of_reminders' => ($statistic ? $statistic->number_of_reminders : 0),
            'number_of_new_users_last_week' => $numberNewUsers,
        ]);

        return $statistics;
    }
}
