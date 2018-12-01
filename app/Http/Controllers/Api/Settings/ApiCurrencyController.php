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
            $currency = Currency::findOrFail($currencyId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new CurrencyResource($currency);
    }
}
