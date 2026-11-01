<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\SendPasswordConfirmationLink;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PasswordConfirmationLinkController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        new SendPasswordConfirmationLink(user: $request->user())->execute();

        return back()->with('status', __('We sent a link to your email address. Open it in this browser to confirm it is you.'));
    }

    public function show(Request $request): RedirectResponse
    {
        abort_unless(hash_equals((string) $request->user()->getKey(), (string) $request->route()->parameter('id')), 404);

        $request->session()->passwordConfirmed();

        return redirect()->intended(route('settings.security.index'));
    }
}
