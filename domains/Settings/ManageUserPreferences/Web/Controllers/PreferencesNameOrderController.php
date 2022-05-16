<?php

namespace App\Settings\ManageUserPreferences\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageUserPreferences\Services\StoreNameOrderPreference;
use App\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
