<?php

namespace App\Settings\ManageHelp\Web\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Settings\ManageHelp\Services\UpdateHelpPreferences;

class HelpController extends Controller
{
    /**
     * Perform search of an employee from the header.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        $request = [
            'user_id' => Auth::user()->id,
            'visibility' => !Auth::user()->help_shown,
        ];

        (new UpdateHelpPreferences())->execute($request);

        return response()->json([
            'data' => !Auth::user()->help_shown,
        ], 200);
    }
}
