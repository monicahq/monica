<?php

namespace App\Http\Controllers\Settings\Preferences;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\User\Preferences\StoreNumberFormatPreference;
use App\Http\Controllers\Settings\Preferences\ViewHelpers\PreferencesIndexViewHelper;

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
            'data' => PreferencesIndexViewHelper::dtoNumberFormat($user),
        ], 200);
    }
}
