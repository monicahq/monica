<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Helpers\ShareLinkPassword;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SharedContactPasswordController extends Controller
{
    public function create(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'max:255'],
        ]);

        ShareLinkPassword::remember($request, $token, $validated['password']);

        return redirect()->route('share.show', ['token' => $token]);
    }
}
