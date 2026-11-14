<?php

namespace App\Domains\Settings\ManageUserPreferences\Web\Controllers;

use App\Domains\Settings\ManageUserPreferences\Services\StoreAvatarStylePreference;
use App\Domains\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreferencesAvatarStyleController extends Controller
{
    public function store(Request $request)
    {
        $request = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'avatar_style' => $request->input('avatarStyle'),
        ];

        (new StoreAvatarStylePreference)->execute($request);

        return response()->json([
            'data' => UserPreferencesIndexViewHelper::dtoAvatarStyle(Auth::user()),
        ], 200);
    }
}
