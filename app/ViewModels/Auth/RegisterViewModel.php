<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Helpers\PasskeyAvailability;

class RegisterViewModel extends GuestViewModel
{
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
