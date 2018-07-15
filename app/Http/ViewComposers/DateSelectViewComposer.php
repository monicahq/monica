<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
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
        $years = DateHelper::getListOfYears(120);

        $view->with([
            'months' => $months,
            'years' => $years,
        ]);
    }
}
