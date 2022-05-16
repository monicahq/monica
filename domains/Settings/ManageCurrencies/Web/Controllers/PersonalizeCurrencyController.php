<?php

namespace App\Settings\ManageCurrencies\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageCurrencies\Services\DisableAllCurrencies;
use App\Settings\ManageCurrencies\Services\EnableAllCurrencies;
use App\Settings\ManageCurrencies\Services\ToggleCurrency;
use App\Settings\ManageCurrencies\Web\ViewHelpers\PersonalizeCurrencyIndexViewHelper;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PersonalizeCurrencyController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/Currencies/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PersonalizeCurrencyIndexViewHelper::data(Auth::user()->account),
        ]);
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

    public function destroy(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
        ];

        (new DisableAllCurrencies)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
