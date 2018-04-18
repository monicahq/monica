<?php

namespace App\Http\Controllers\Api\Misc;

use Illuminate\Http\Request;
use App\Helpers\CountriesHelper;
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
        $countries = CountriesHelper::getAll();

        return CountryResource::collection($countries);
    }
}
