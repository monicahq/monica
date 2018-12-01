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

namespace App\Http\Controllers\Api\Misc;

use Illuminate\Http\Request;
use App\Helpers\LocaleHelper;
use App\Helpers\CountriesHelper;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Country\Country as CountryResource;

class ApiCountryController extends ApiController
{
    /**
     * Get the list of countries.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $key = 'countries.'.LocaleHelper::getLocale();

        $countries = Cache::rememberForever($key, function () {
            return CountriesHelper::getAll();
        });

        return CountryResource::collection($countries);
    }
}
