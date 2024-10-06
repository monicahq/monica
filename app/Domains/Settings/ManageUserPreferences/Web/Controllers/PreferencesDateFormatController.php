<?php

namespace App\Domains\Settings\ManageUserPreferences\Web\Controllers;

use App\Domains\Settings\ManageUserPreferences\Services\StoreDateFormatPreference;
use App\Domains\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreferencesDateFormatController extends Controller
{
    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'date_format' => $request->input('dateFormat'),
        ];

        $user = (new StoreDateFormatPreference)->execute($data);

        return response()->json([
            'data' => UserPreferencesIndexViewHelper::dtoDateFormat($user),
        ], 200);
    }
}
