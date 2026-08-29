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
    /**
     * The current password is checked by the rule of the same name, which reads
     * the hash off the signed in account, so a wrong one never reaches the
     * action.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'new_password' => ['required', 'string', 'max:255', 'confirmed', Password::min(8)],
        ]);

        new UpdateUserPassword(
            user: $request->user(),
            password: $validated['new_password'],
        )->execute();

        return redirect()->route('settings.security.index')
            ->with('status', __('Your password is changed.'))
            ->with('status_description', __('Use it the next time you sign in.'));
    }

    /**
     * Set a password on an account that has none.
     *
     * There is no `current_password` rule, because there is no current password
     * to confirm. That is the whole reason this is a route of its own rather
     * than a branch inside `update`: the rule cannot be made conditional without
     * making the screen lie about what it is checking.
     */
    public function create(Request $request): RedirectResponse
    {
        if ($request->user()->password !== null) {
            throw ValidationException::withMessages([
                'new_password' => __('This account already has a password. Change it instead.'),
            ]);
        }

        $validated = $request->validate([
            'new_password' => ['required', 'string', 'max:255', 'confirmed', Password::min(8)],
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

        return redirect()->route('settings.security.index')
            ->with('status', __('Your password is removed.'))
            ->with('status_description', __('Your passkeys, and a link sent to your email address, are what sign you in now.'));
    }
}
