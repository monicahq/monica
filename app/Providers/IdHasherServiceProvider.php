<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



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
