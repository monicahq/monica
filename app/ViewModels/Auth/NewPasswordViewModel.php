<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

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
}
