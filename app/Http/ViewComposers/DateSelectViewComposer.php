<?php

namespace App\Http\ViewComposers;

use Carbon\Carbon;
use Illuminate\View\View;

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
        Carbon::setLocale(auth()->user()->locale);
        $months = [];
        $currentDate = Carbon::now();

        for ($month = 1; $month < 13; $month++) {
            $currentDate->month = $month;
            array_push($months, $currentDate->formatLocalized('%B'));
        }

        // Years
        $years = [];
        $maxYear = Carbon::now(auth()->user()->timezone)->year;
        $minYear = Carbon::now(auth()->user()->timezone)->subYears(120)->format('Y');

        for ($year = $minYear; $year <= $maxYear; $year++) {
            array_push($years, $year);
        }

        $view->with([
            'months' => $months,
            'years' => $years,
        ]);
    }
}
