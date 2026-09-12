<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Security;

use App\Models\User;

class RecoveryCodesViewModel
{
    /** @param  array<int, string>  $codes */
    public function __construct(
        private readonly User $user,
        private readonly array $codes,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    /** @return array<int, string> */
    public function codes(): array
    {
        return $this->codes;
    }

    public function hasCodes(): bool
    {
        return $this->codes !== [];
    }
}
