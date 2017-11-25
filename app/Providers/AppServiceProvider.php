<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Laravel\Dusk\DuskServiceProvider;
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
            'partials.components.country-select', 'App\Http\ViewComposers\CountrySelectViewComposer'
        );

        View::composer(
            'partials.components.currency-select', 'App\Http\ViewComposers\CurrencySelectViewComposer'
        );

        View::composer(
            'partials.components.date-select', 'App\Http\ViewComposers\DateSelectViewComposer'
        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local', 'testing')) {
            $this->app->register(DuskServiceProvider::class);
        }
    }
}
