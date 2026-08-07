<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

class VerifyEmailViewModel extends GuestViewModel
{
    public function __construct(
        private readonly string $email,
    ) {}

    public function email(): string
    {
        return $this->email;
    }
}
