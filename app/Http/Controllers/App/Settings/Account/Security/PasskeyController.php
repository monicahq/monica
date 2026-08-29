<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Actions\DestroyPasskey;
use App\Actions\UpdatePasskey;
use App\Http\Controllers\Controller;
use App\Models\WebauthnKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Renaming and removing a passkey.
 *
 * Adding one and signing in with one go through the package, which fires events
 * this application listens to. It fires nothing when a key is renamed or
 * deleted, so those two operations are here, where the activity entry, the
 * notification and the message naming what still signs somebody in can be
 * written down.
 */
class PasskeyController extends Controller
{
    public function update(Request $request, WebauthnKey $passkey): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        new UpdatePasskey(
            user: $request->user(),
            passkey: $passkey,
            name: $validated['name'],
        )->execute();

        return redirect()->route('settings.security.index')
            ->with('status', __('Your passkey is renamed.'));
    }

    public function destroy(Request $request, WebauthnKey $passkey): RedirectResponse
    {
        new DestroyPasskey(
            user: $request->user(),
            passkey: $passkey,
        )->execute();

        return redirect()->route('settings.security.index')
            ->with('status', __('That passkey is removed.'))
            ->with('status_description', $this->whatIsLeft($request));
    }

    /**
     * What still signs them in, said out loud rather than left to be worked out.
     * Removing a way into your own account is the moment to be told what is
     * left, not the moment to go looking.
     */
    private function whatIsLeft(Request $request): string
    {
        $user = $request->user();
        $remaining = $user->passkeys()->count();

        if ($remaining > 0) {
            return trans_choice(
                'You still have :count passkey, your password, and a link sent by email.|You still have :count passkeys, your password, and a link sent by email.',
                $remaining,
            );
        }

        if ($user->usesPasswordlessSignIn()) {
            return __('You have no passkeys and no password left. A link sent to your email address is now the only way in.');
        }

        return __('Your password and a link sent by email still work.');
    }
}
