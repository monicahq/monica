<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Contracts\PasskeyDeletedResponse as PasskeyDeletedResponseContract;

/**
 * What the security screen says once a passkey has been removed.
 *
 * The package answers with a status key and nothing else. Removing a way into
 * your own account is the moment to be told what is left, not the moment to go
 * looking, so the message naming what still signs somebody in is built here.
 */
class PasskeyDeletedResponse implements PasskeyDeletedResponseContract
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): RedirectResponse
    {
        return redirect()->route('settings.security.index')
            ->with('status', __('That passkey is removed.'))
            ->with('status_description', $this->whatIsLeft($request));
    }

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
