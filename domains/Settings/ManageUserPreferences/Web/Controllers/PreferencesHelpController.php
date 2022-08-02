<?php

namespace App\Settings\ManageUserPreferences\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageUserPreferences\Services\StoreHelpPreference;
use App\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreferencesHelpController extends Controller
{
    public function store(Request $request)
    {
        $request = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'visibility' => ! Auth::user()->help_shown,
        ];

        (new StoreHelpPreference())->execute($request);

        return response()->json([
            'data' => UserPreferencesIndexViewHelper::dtoHelp(Auth::user()),
        ], 200);
    }
}
