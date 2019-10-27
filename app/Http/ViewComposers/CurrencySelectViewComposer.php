<?php

namespace App\Http\ViewComposers;

use App\Models\Settings\Currency;
use Illuminate\View\View;

class CurrencySelectViewComposer
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
        $currencies = Currency::orderBy('name', 'asc')->get();
        $view->with('currencies', $currencies);
    }
}
