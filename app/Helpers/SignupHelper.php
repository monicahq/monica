<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Account;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class SignupHelper
{
    protected ConfigRepository $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    public function isEnabled(): bool
    {
        return !($this->isDisabledByConfig() && $this->hasAtLeastOneAccount());
    }

    protected function isDisabledByConfig(): bool
    {
        return (bool) $this->configRepository->get('monica.disable_signup');
    }

    protected function hasAtLeastOneAccount(): bool
    {
        return !empty(Account::first());
    }
}
