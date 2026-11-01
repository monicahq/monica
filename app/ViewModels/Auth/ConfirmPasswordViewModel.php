<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Helpers\PasskeyAvailability;
use App\Models\User;

class ConfirmPasswordViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function offersPassword(): bool
    {
        return $this->user->password !== null;
    }

    public function offersPasskey(): bool
    {
        return $this->user->hasPasskeys() && PasskeyAvailability::onThisInstance();
    }

    public function passkeysUnavailableReason(): ?string
    {
        return PasskeyAvailability::reason();
    }
}
