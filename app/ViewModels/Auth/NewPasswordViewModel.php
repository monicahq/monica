<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Helpers\PasswordRequirements;

class NewPasswordViewModel extends GuestViewModel
{
    public function __construct(
        private readonly string $token,
        private readonly string $email,
    ) {}

    public function token(): string
    {
        return $this->token;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function passwordRules(): string
    {
        return PasswordRequirements::passwordRules();
    }

    public function passwordRequirements(): string
    {
        return PasswordRequirements::description();
    }
}
