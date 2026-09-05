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

        LocaleResolution::remember($validated['locale']);

        return back();
    }
}
