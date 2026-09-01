<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\CreateAccount;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Turnstile;
use App\ViewModels\Auth\RegisterPasskeyViewModel;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Creating an account whose only credential is a passkey.
 *
 * The order is forced by the protocol rather than chosen: a passkey is created
 * against an account that already exists, because the user handle is part of
 * what gets signed. So `create` makes the account with no password and signs
 * them in, and `new` is the step where the device is asked for the credential.
 *
 * A prompt abandoned between the two leaves an account holding neither a passkey
 * nor a password. Nothing here undoes it: the account is real, and a link sent to
 * the address reaches it. The screen says so and offers both routes again.
 *
 * Everything the password route validates is validated here except the password,
 * so that neither route is the lax one.
 */
class RegistrationPasskeyController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class, 'disposable_email'],
            'timezone' => ['nullable', 'string', 'timezone:all'],
            'terms' => ['accepted'],
        ];

        if (config('turnstile.enabled')) {
            $rules['cf-turnstile-response'] = ['required', new Turnstile];
        }

        $validated = $request->validate($rules, [
            'terms.accepted' => __('You have to agree with the terms of use and the privacy policy to create an account.'),
        ]);

        $user = new CreateAccount(
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            email: $validated['email'],
            timezone: $validated['timezone'] ?? null,
            locale: App::currentLocale(),
        )->execute();

        Auth::login($user);

        return redirect()->route('auth.registerPasskey.new');
    }

    public function new(Request $request): View
    {
        return view('app.auth.register-passkey', [
            'viewModel' => new RegisterPasskeyViewModel(
                user: $request->user(),
            ),
        ]);
    }
}
