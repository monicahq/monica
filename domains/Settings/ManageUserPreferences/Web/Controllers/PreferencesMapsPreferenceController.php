<?php

namespace App\Settings\ManageUserPreferences\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageUserPreferences\Services\StoreMapsPreference;
use App\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreferencesMapsPreferenceController extends Controller
{
    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'maps_site' => $request->input('value'),
        ];

        $user = (new StoreMapsPreference())->execute($data);

        return response()->json([
            'data' => UserPreferencesIndexViewHelper::dtoMapsPreferences($user),
        ], 200);
    }
}
