<?php

namespace App\Domains\Settings\ManageUserPreferences\Web\Controllers;

use App\Domains\Settings\ManageUserPreferences\Services\StoreDistanceFormatPreference;
use App\Domains\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreferencesDistanceFormatController extends Controller
{
    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'distance_format' => $request->input('distanceFormat'),
        ];

        $user = (new StoreDistanceFormatPreference)->execute($data);

        return response()->json([
            'data' => UserPreferencesIndexViewHelper::dtoDistanceFormat($user),
        ], 200);
    }
}
