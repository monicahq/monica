<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Actions\DestroyUserPassword;
use App\Actions\UpdateUserPassword;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'new_password' => ['required', 'string', 'max:255', 'confirmed', Password::default()],
        ]);

        new UpdateUserPassword(
            user: $request->user(),
            password: $validated['new_password'],
        )->execute();

        $request->session()->forget('auth.password_confirmed_at');

        return redirect()->route('settings.security.index')
            ->with('status', __('Your password is changed.'))
            ->with('status_description', __('Use it the next time you sign in.'));
    }

    public function create(Request $request): RedirectResponse
    {
        if ($request->user()->password !== null) {
            throw ValidationException::withMessages([
                'new_password' => __('This account already has a password. Change it instead.'),
            ]);
        }

        $validated = $request->validate([
            'new_password' => ['required', 'string', 'max:255', 'confirmed', Password::default()],
        ]);

        new UpdateUserPassword(
            user: $request->user(),
            password: $validated['new_password'],
        )->execute();

        return redirect()->route('settings.security.index')
            ->with('status', __('Your password is set.'))
            ->with('status_description', __('You can sign in with it, or with any passkey you hold.'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        new DestroyUserPassword(
            user: $request->user(),
        )->execute();

        $request->session()->forget('auth.password_confirmed_at');

        return redirect()->route('settings.security.index')
            ->with('status', __('Your password is removed.'))
            ->with('status_description', __('Your passkeys, and a link sent to your email address, are what sign you in now.'));
    }
}
