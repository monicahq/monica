<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Helpers\PasskeyAvailability;
use App\Helpers\PasswordRequirements;
use App\Models\PreviewListEntry;

class RegisterViewModel extends GuestViewModel
{
    public function __construct(
        private readonly ?PreviewListEntry $previewListEntry = null,
        private readonly ?string $admission = null,
    ) {}

    public function admittedEmail(): ?string
    {
        return $this->previewListEntry?->email;
    }

    public function admission(): ?string
    {
        return $this->admission;
    }

    public function passkeysAreAvailable(): bool
    {
        return PasskeyAvailability::onThisInstance();
    }

    public function registrationMethod(): string
    {
        if (! $this->passkeysAreAvailable()) {
            return 'password';
        }

        return old('registration_method') === 'passkey' ? 'passkey' : 'password';
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
