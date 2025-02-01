<?php

namespace App\Domains\Settings\ManageUserPreferences\Web\Controllers;

use App\Domains\Settings\ManageUserPreferences\Services\StoreTimezone;
use App\Domains\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreferencesTimezoneController extends Controller
{
    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'timezone' => $request->input('timezone'),
        ];

        $user = (new StoreTimezone)->execute($data);

        return response()->json([
            'data' => UserPreferencesIndexViewHelper::dtoTimezone($user),
        ], 200);
    }
}
