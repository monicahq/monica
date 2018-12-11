<?php

namespace App\Http\ViewComposers;

use App\Models\Instance\Instance;
use Illuminate\View\View;

class InstanceViewComposer
{
    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $instance = Instance::first();

        $view->with('instance', $instance);
    }
}
