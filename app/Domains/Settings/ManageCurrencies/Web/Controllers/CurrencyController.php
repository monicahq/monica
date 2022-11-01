<?php

namespace App\Domains\Settings\ManageCurrencies\Web\Controllers;

use App\Domains\Settings\ManageCurrencies\Web\ViewHelpers\CurrencyIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CurrencyController extends Controller
{
    public function index(): JsonResponse
    {
        $currenciesCollection = CurrencyIndexViewHelper::data(Auth::user()->account);

        return response()->json([
            'data' => $currenciesCollection,
        ], 201);
    }
}
