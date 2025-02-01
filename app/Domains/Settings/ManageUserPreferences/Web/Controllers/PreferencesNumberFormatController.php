<?php

namespace App\Domains\Settings\ManageUserPreferences\Web\Controllers;

use App\Domains\Settings\ManageUserPreferences\Services\StoreNumberFormatPreference;
use App\Domains\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreferencesNumberFormatController extends Controller
{
    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'number_format' => $request->input('numberFormat'),
        ];

        $user = (new StoreNumberFormatPreference)->execute($data);

        return response()->json([
            'data' => UserPreferencesIndexViewHelper::dtoNumberFormat($user),
        ], 200);
    }
}
