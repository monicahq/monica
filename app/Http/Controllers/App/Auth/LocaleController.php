<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Helpers\LocaleResolution;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(config('monica.locales')))],
        ]);

        // The session, the device and the screen being returned to, in one call,
        // so the language cannot be showing one thing and stored as another.
        LocaleResolution::remember($validated['locale']);

        return back();
    }
}
