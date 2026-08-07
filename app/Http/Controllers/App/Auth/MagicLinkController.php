<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\ConsumeMagicLink;
use App\Actions\CreateMagicLink;
use App\Http\Controllers\Controller;
use App\ViewModels\Auth\MagicLinkViewModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MagicLinkController extends Controller
{
    public function new(): View
    {
        return view('app.auth.request-magic-link', [
            'viewModel' => new MagicLinkViewModel,
        ]);
    }

    public function create(Request $request): View
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'disposable_email'],
        ]);

        // An address with no account behind it gets the same screen as one that
        // has, so this form cannot be used to find out who is a member here.
        try {
            new CreateMagicLink(email: $validated['email'])->execute();
        } catch (ModelNotFoundException) {
        }

        return view('app.auth.magic-link-sent', [
            'viewModel' => new MagicLinkViewModel,
        ]);
    }

    public function show(Request $request): RedirectResponse
    {
        try {
            $user = new ConsumeMagicLink(
                token: (string) $request->route()->parameter('token'),
                ip: $request->ip(),
            )->execute();
        } catch (ModelNotFoundException) {
            return redirect()->route('auth.magicLink.new')
                ->withErrors(['email' => __('That link no longer works. Ask for another one.')]);
        }

        // A link that skips the password must not also skip the challenge, so
        // somebody who enrolled answers it here exactly as they would after
        // typing a password. The session only remembers who they claim to be.
        if ($user->usesTwoFactorAuthentication()) {
            $request->session()->put('twoFactor.user.id', $user->id);

            return redirect()->route('auth.twoFactor.new');
        }

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        return redirect()->intended(route('home.index'));
    }
}
