<?php

namespace App\Http\Controllers\Settings;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User\UserToken;
use App\Http\Controllers\Controller;

class TokensController extends Controller
{
    /**
     * Get all the reminder rules.
     */
    public function create(Request $request)
    {
        $token = Str::random(64);

        UserToken::forceCreate([
            'user_id' => auth()->user()->id,
            'api_token' => hash('sha256', $token),
            'dav_resource' => $request->input('resource')
        ]);

        return ['token' => $token];
    }
}
