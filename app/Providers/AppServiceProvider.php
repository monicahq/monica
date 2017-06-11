<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        'partials.components.country-select','App\Http\ViewComposers\CountrySelectViewComposer'
      );

      View::composer(
        'partials.components.currency-select','App\Http\ViewComposers\CurrencySelectViewComposer'
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
