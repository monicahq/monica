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
    public function new(Request $request): View|RedirectResponse
    {
        if ($this->asksToCreateAnAccount($request)) {
            if (config('monica.registration_enabled')) {
                return redirect()->route('auth.register.new');
            }

            $request->session()->now('status', __('This instance does not accept new accounts. Log in, or ask the person who runs it for an account.'));
        }

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

        if ($user->usesTwoFactorAuthentication()) {
            Auth::guard('web')->logout();
            $request->session()->put('twoFactor.user.id', $user->id);
            $request->session()->put('twoFactor.remember', $request->boolean('remember'));
            $request->session()->forget(['twoFactor.signInMethod', 'twoFactor.provider']);

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

    /**
     * Whether the person was sent here by the mobile app tapping Sign up: an authorization request
     * waiting for them to sign in, whose prompt asks for an account to be created.
     */
    private function asksToCreateAnAccount(Request $request): bool
    {
        $intended = (string) $request->session()->get('url.intended');

        if (! str_starts_with($intended, route('passport.authorizations.authorize'))) {
            return false;
        }

        parse_str((string) parse_url($intended, PHP_URL_QUERY), $query);

        return in_array('create', explode(' ', (string) ($query['prompt'] ?? '')), true);
    }
}
