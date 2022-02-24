<?php

namespace App\Http\Controllers\Settings\Preferences;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\User\Preferences\StoreTimezone;
use App\Http\Controllers\Settings\Preferences\ViewHelpers\PreferencesIndexViewHelper;

class PreferencesTimezoneController extends Controller
{
    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'timezone' => $request->input('timezone'),
        ];

        $user = (new StoreTimezone)->execute($data);

        return response()->json([
            'data' => PreferencesIndexViewHelper::dtoTimezone($user),
        ], 200);
    }
}
