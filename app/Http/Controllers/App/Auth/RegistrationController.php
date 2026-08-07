<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\CreateCompany;
use App\Actions\SendEmailVerification;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Turnstile;
use App\ViewModels\Auth\RegisterViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function new(): View
    {
        return view('app.auth.register', [
            'viewModel' => new RegisterViewModel,
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class, 'disposable_email'],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
        ];

        if (config('turnstile.enabled')) {
            $rules['cf-turnstile-response'] = ['required', new Turnstile];
        }

        $validated = $request->validate($rules, [
            'terms.accepted' => __('You have to agree with the terms of use and the privacy policy to create an account.'),
        ]);

        $company = new CreateCompany(
            name: $validated['company_name'],
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            email: $validated['email'],
            password: $validated['password'],
        )->execute();

        $user = $company->owner;

        new SendEmailVerification(user: $user)->execute();

        Auth::login($user);

        return redirect()->route('auth.verification.notice');
    }
}
