<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Helpers\PasskeyAvailability;

class LoginViewModel extends GuestViewModel
{
    /**
     * Whether the screen may offer to sign in with a passkey.
     *
     * The browser checks too, and it has the last word, because only it knows
     * what the device in front of the person can do. This check is about the
     * instance: served over a connection a passkey will not travel on, or with
     * the feature switched off, the control is not rendered at all and the
     * reason takes its place. A control that fails when used is worse than one
     * that is not there.
     */
    public function passkeysAreAvailable(): bool
    {
        return PasskeyAvailability::onThisInstance();
    }

    /**
     * Why passkeys cannot be used here, or null when they can.
     */
    public function passkeysUnavailableReason(): ?string
    {
        return PasskeyAvailability::reason();
    }
}
