<?php

namespace App\Domains\Settings\ManageUserPreferences\Web\Controllers;

use App\Domains\Settings\ManageUserPreferences\Services\StoreMapsPreference;
use App\Domains\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreferencesMapsPreferenceController extends Controller
{
    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'maps_site' => $request->input('value'),
        ];

        $user = (new StoreMapsPreference)->execute($data);

        return response()->json([
            'data' => UserPreferencesIndexViewHelper::dtoMapsPreferences($user),
        ], 200);
    }
}
