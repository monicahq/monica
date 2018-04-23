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
}
