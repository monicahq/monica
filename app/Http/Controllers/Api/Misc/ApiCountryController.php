<?php

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
