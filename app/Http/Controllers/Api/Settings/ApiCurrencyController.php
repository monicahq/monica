<?php

namespace App\Http\Controllers\Api\Settings;

use Illuminate\Http\Request;
use App\Models\Settings\Currency;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Settings\Currency\Currency as CurrencyResource;

class ApiCurrencyController extends ApiController
{
    /**
     * Get the list of currencies.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $currencies = Currency::paginate($this->getLimitPerPage());

        return CurrencyResource::collection($currencies);
    }

    /**
     * Get the detail of a given currency.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $currencyId)
    {
        try {
            $currency = Currency::find($currencyId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new CurrencyResource($currency);
    }
}
