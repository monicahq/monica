<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use Jenssegers\Date\Date;

class DateSelectViewComposer
{
    public function __construct()
    {
    }

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        // Months
        Date::setLocale(auth()->user()->locale);
        $months = [];
        $currentDate = Date::now();
        $currentDate->day = 1;

        for ($month = 1; $month < 13; $month++) {
            $currentDate->month = $month;
            array_push($months, mb_convert_case($currentDate->format('F'), MB_CASE_TITLE, 'UTF-8'));
        }

        // Years
        $years = [];
        $maxYear = Date::now(auth()->user()->timezone)->year;
        $minYear = Date::now(auth()->user()->timezone)->subYears(120)->format('Y');

        for ($year = $maxYear; $year >= $minYear; $year--) {
            array_push($years, $year);
        }

        $view->with([
            'months' => $months,
            'years' => $years,
        ]);
    }
}
