<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Helpers\PasskeyAvailability;
use App\Models\User;

/**
 * What the step after account creation shows: whether the credential this
 * account was meant to have actually exists yet.
 */
class RegisterPasskeyViewModel extends GuestViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function firstName(): string
    {
        return $this->user->first_name;
    }

    public function hasPasskeys(): bool
    {
        return $this->user->hasPasskeys();
    }

    /**
     * Whether the account is currently reachable only by a link sent to its
     * address, which is what an abandoned prompt leaves behind.
     */
    public function hasNoCredentialAtAll(): bool
    {
        return $this->user->usesPasswordlessSignIn() && ! $this->user->hasPasskeys();
    }

    public function passkeysAreAvailable(): bool
    {
        return PasskeyAvailability::onThisInstance();
    }

    public function passkeysUnavailableReason(): ?string
    {
        return PasskeyAvailability::reason();
    }
}
