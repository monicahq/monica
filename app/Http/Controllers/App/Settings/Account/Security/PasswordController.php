<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Actions\UpdateUserPassword;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

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
}
