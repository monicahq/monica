<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Models\Instance\Instance;

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
