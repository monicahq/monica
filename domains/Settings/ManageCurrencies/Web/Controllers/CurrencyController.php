<?php

namespace App\Settings\ManageCurrencies\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageCurrencies\Web\ViewHelpers\CurrencyIndexViewHelper;
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
