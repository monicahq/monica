<?php

namespace App\Http\Controllers\Settings\Personalize\Currencies;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Account\ManageCurrencies\ToggleCurrency;
use App\Services\Account\ManageCurrencies\EnableAllCurrencies;
use App\Services\Account\ManageCurrencies\DisableAllCurrencies;
use App\Http\Controllers\Vault\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Settings\Personalize\Currencies\ViewHelpers\PersonalizeCurrencyIndexViewHelper;

class PersonalizeCurrencyController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/Currencies/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PersonalizeCurrencyIndexViewHelper::data(Auth::user()->account),
        ]);
    }

    public function update(Request $request, int $currencyId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'currency_id' => $currencyId,
        ];

        (new ToggleCurrency)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
        ];

        (new EnableAllCurrencies)->execute($data);

        return response()->json([
            'data' => true,
        ], 201);
    }

    public function destroy(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
        ];

        (new DisableAllCurrencies)->execute($data);

        return response()->json([
            'data' => true,
        ], 201);
    }
}
