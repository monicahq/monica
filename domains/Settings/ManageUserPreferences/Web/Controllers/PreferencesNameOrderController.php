<?php

namespace App\Settings\ManageUserPreferences\Web\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Settings\ManageUserPreferences\Services\StoreNameOrderPreference;
use App\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;

class PreferencesNameOrderController extends Controller
{
    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'name_order' => $request->input('nameOrder'),
        ];

        $user = (new StoreNameOrderPreference)->execute($data);

        return response()->json([
            'data' => UserPreferencesIndexViewHelper::dtoNameOrder($user),
        ], 200);
    }
}
