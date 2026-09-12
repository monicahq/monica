<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SsoProviderEnum;
use App\Models\User;

/**
 * Create an account for somebody a provider has just vouched for. It is the
 * ordinary account creation with the password left out. The address is left
 * unconfirmed, so the registration event sends the verification email and the
 * person confirms the address the way anybody who registers here does.
 */
class CreateAccountFromProvider
{
    public function __construct(
        private readonly SsoProviderEnum $provider,
        private readonly string $ssoId,
        private readonly string $email,
        private readonly string $firstName,
        private readonly string $lastName,
        private readonly ?string $timezone = null,
        private readonly ?string $locale = null,
    ) {}

    public function execute(): User
    {
        return new CreateAccount(
            firstName: $this->firstName,
            lastName: $this->lastName,
            email: $this->email,
            timezone: $this->timezone,
            locale: $this->locale,
            ssoProvider: $this->provider,
            ssoId: $this->ssoId,
        )->execute();
    }
}
