<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use Jenssegers\Date\Date;
use App\Helpers\DateHelper;

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
        $months = DateHelper::getListOfMonths();

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
