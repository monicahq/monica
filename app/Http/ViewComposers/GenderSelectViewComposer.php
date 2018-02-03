<?php

namespace App\Http\ViewComposers;

use Carbon\Carbon;
use Illuminate\View\View;

class GenderSelectViewComposer
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
        $genders = auth()->user()->account->genders;

        $view->with([
            'genders' => $genders,
        ]);
    }
}
