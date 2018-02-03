<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer(
            'partials.components.currency-select', 'App\Http\ViewComposers\CurrencySelectViewComposer'
        );

        View::composer(
            'partials.components.date-select', 'App\Http\ViewComposers\DateSelectViewComposer'
        );

        View::composer(
            'partials.components.gender-select', 'App\Http\ViewComposers\GenderSelectViewComposer'
        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
