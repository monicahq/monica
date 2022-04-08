<?php

namespace App\Settings\ManageUserPreferences\Web\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Settings\ManageUserPreferences\Services\StoreDateFormatPreference;
use App\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;

class PreferencesDateFormatController extends Controller
{
    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'date_format' => $request->input('dateFormat'),
        ];

        $user = (new StoreDateFormatPreference)->execute($data);

        return response()->json([
            'data' => UserPreferencesIndexViewHelper::dtoDateFormat($user),
        ], 200);
    }
}
