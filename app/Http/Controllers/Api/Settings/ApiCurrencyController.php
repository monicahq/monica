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
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $currencies = Currency::paginate($this->getLimitPerPage());

        return CurrencyResource::collection($currencies);
    }

    /**
     * Get the detail of a given currency.
     *
     * @param Request $request
     *
     * @return CurrencyResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $currencyId)
    {
        try {
            $currency = Currency::findOrFail($currencyId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new CurrencyResource($currency);
    }
}
