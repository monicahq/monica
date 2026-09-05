<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\CreateAccount;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Turnstile;
use App\ViewModels\Auth\RegisterPasskeyViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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
