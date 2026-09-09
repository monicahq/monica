<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Helpers\PasskeyAvailability;

class LoginViewModel extends GuestViewModel
{
    public function passkeysAreAvailable(): bool
    {
        return PasskeyAvailability::onThisInstance();
    }

    public function passkeysUnavailableReason(): ?string
    {
        return PasskeyAvailability::reason();
    }

    public function magicLinkIsEnabled(): bool
    {
        return (bool) config('monica.magic_link_enabled');
    }
}
