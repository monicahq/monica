<?php

namespace App\Http\ViewComposers;

use App\Helpers\CountriesHelper;
use Illuminate\View\View;

class CountrySelectViewComposer
{
    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $countries = CountriesHelper::getAll()->all();

        $view->with('countries', $countries);
    }
}
