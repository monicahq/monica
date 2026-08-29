<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Helpers\PasskeyAvailability;

class RegisterViewModel extends GuestViewModel
{
    /**
     * Whether the screen may offer to create a passkey.
     *
     * When it may not, the password route is all there is, and the screen says
     * why rather than showing a control that would fail. The browser checks too
     * and has the last word.
     */
    public function passkeysAreAvailable(): bool
    {
        return PasskeyAvailability::onThisInstance();
    }

    public function passkeysUnavailableReason(): ?string
    {
        return PasskeyAvailability::reason();
    }

    public function termsUrl(): string
    {
        return config('monica.terms_url');
    }

    public function privacyUrl(): string
    {
        return config('monica.privacy_url');
    }
}
