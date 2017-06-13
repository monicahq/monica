<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Country;

class CountrySelectViewComposer
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
      $countries = Country::orderBy('country', 'asc')->get();
      $view->with('countries', $countries );
    }
}
