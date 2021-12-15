<?php

namespace App\Http\Controllers\Settings\Preferences;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\User\StoreNameOrderPreference;
use App\Http\Controllers\Vault\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Settings\Preferences\ViewHelpers\PreferencesIndexViewHelper;

class PreferencesController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Preferences/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PreferencesIndexViewHelper::data(Auth::user()),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'name_order' => $request->input('nameOrder'),
        ];

        $user = (new StoreNameOrderPreference)->execute($data);

        return response()->json([
            'data' => PreferencesIndexViewHelper::dtoNameOrder($user),
        ], 200);
    }
}
