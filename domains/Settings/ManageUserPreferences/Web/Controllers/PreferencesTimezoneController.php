<?php

namespace App\Settings\ManageUserPreferences\Web\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Settings\ManageUserPreferences\Services\StoreTimezone;
use App\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;

class PreferencesTimezoneController extends Controller
{
    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'timezone' => $request->input('timezone'),
        ];

        $user = (new StoreTimezone)->execute($data);

        return response()->json([
            'data' => UserPreferencesIndexViewHelper::dtoTimezone($user),
        ], 200);
    }
}
