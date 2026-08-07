<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\AttemptLogin;
use App\Http\Controllers\Controller;
use App\Rules\Turnstile;
use App\ViewModels\Auth\LoginViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function new(): View
    {
        return view('app.auth.login', [
            'viewModel' => new LoginViewModel,
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $rules = [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ];

        if (config('turnstile.enabled')) {
            $rules['cf-turnstile-response'] = ['required', new Turnstile];
        }

        $validated = $request->validate($rules);

        $user = new AttemptLogin(
            email: $validated['email'],
            password: $validated['password'],
            remember: $request->boolean('remember'),
            ip: $request->ip(),
        )->execute();

        // Somebody who enrolled in two factor authentication is not signed in
        // until they answer the challenge, so the session only remembers who
        // they claim to be.
        if ($user->usesTwoFactorAuthentication()) {
            Auth::guard('web')->logout();
            $request->session()->put('twoFactor.user.id', $user->id);

            return redirect()->route('auth.twoFactor.new');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home.index');
    }
}
