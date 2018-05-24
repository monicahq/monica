<?php

namespace App\Providers;

use App\Helpers\IdHasher;
use Illuminate\Support\ServiceProvider;

class IdHasherServiceProvider extends ServiceProvider
{
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['idhasher'];
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('idhasher', function ($app) {
            return $app->make(IdHasher::class);
        });
    }
}
