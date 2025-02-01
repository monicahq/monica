<?php

namespace App\Domains\Settings\ManageUserPreferences\Web\Controllers;

use App\Domains\Settings\ManageUserPreferences\Services\StoreHelpPreference;
use App\Domains\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PreferencesHelpController extends Controller
{
    public function store()
    {
        $request = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'visibility' => ! Auth::user()->help_shown,
        ];

        (new StoreHelpPreference)->execute($request);

        return response()->json([
            'data' => UserPreferencesIndexViewHelper::dtoHelp(Auth::user()),
        ], 200);
    }
}
