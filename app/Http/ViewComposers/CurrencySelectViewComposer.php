<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Models\Settings\Currency;

class CurrencySelectViewComposer
{
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
