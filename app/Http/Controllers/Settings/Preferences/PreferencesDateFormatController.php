<?php

namespace App\Http\Controllers\Settings\Preferences;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\User\Preferences\StoreDateFormatPreference;
use App\Http\Controllers\Settings\Preferences\ViewHelpers\PreferencesIndexViewHelper;

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
            'data' => PreferencesIndexViewHelper::dtoDateFormat($user),
        ], 200);
    }
}
