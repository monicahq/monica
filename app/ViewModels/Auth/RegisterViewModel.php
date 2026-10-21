<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Helpers\PasskeyAvailability;
use App\Helpers\PasswordRequirements;

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

    public function registrationMethod(): string
    {
        if (! $this->passkeysAreAvailable()) {
            return 'password';
        }

        return old('registration_method') === 'password' ? 'password' : 'passkey';
    }

    public function passwordRules(): string
    {
        return PasswordRequirements::passwordRules();
    }

    public function passwordRequirements(): string
    {
        return PasswordRequirements::description();
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
