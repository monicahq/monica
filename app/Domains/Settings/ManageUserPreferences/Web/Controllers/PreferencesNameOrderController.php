<?php

namespace App\Domains\Settings\ManageUserPreferences\Web\Controllers;

use App\Domains\Settings\ManageUserPreferences\Services\StoreNameOrderPreference;
use App\Domains\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreferencesNameOrderController extends Controller
{
    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'name_order' => $request->input('nameOrder'),
        ];

        $user = (new StoreNameOrderPreference)->execute($data);

        return response()->json([
            'data' => UserPreferencesIndexViewHelper::dtoNameOrder($user),
        ], 200);
    }
}
