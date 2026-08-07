<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\ResetPassword;
use App\Http\Controllers\Controller;
use App\ViewModels\Auth\NewPasswordViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('app.auth.reset-password', [
            'viewModel' => new NewPasswordViewModel(
                token: (string) $request->route()->parameter('token'),
                email: (string) $request->query('email'),
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::min(8)],
        ]);

        new ResetPassword(
            email: $validated['email'],
            token: $validated['token'],
            password: $validated['password'],
        )->execute();

        return redirect()->route('auth.login.new')
            ->with('status', __('Your password is changed. You can sign in with it now.'));
    }
}
