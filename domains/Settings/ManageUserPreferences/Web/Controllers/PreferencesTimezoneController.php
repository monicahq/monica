<?php

namespace App\Settings\ManageUserPreferences\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageUserPreferences\Services\StoreTimezone;
use App\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
