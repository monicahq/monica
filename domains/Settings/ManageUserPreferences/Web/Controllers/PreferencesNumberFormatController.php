<?php

namespace App\Settings\ManageUserPreferences\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageUserPreferences\Services\StoreNumberFormatPreference;
use App\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreferencesNumberFormatController extends Controller
{
    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'number_format' => $request->input('numberFormat'),
        ];

        $user = (new StoreNumberFormatPreference)->execute($data);

        return response()->json([
            'data' => UserPreferencesIndexViewHelper::dtoNumberFormat($user),
        ], 200);
    }
}
