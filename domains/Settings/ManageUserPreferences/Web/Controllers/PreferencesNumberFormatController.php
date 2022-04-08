<?php

namespace App\Settings\ManageUserPreferences\Web\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Settings\ManageUserPreferences\Services\StoreNumberFormatPreference;
use App\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;

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
