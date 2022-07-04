<?php

namespace App\Settings\ManageUserPreferences\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageUserPreferences\Services\StoreLocale;
use App\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreferencesLocaleController extends Controller
{
    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'locale' => $request->input('locale'),
        ];

        $user = (new StoreLocale())->execute($data);

        return response()->json([
            'data' => UserPreferencesIndexViewHelper::dtoLocale($user),
        ], 200);
    }
}
