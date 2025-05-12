<?php

namespace App\Helpers;

use App\Models\Account;
use Illuminate\Config\Repository as Config;

class SignupHelper
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(
        private Config $config
    ) {}

    /**
     * Check if signup is enabled.
     */
    public function isEnabled(): bool
    {
        return ! ($this->isDisabledByConfig() && $this->hasAtLeastOneAccount());
    }

    private function isDisabledByConfig(): bool
    {
        return $this->config->get('monica.disable_signup', false);
    }

    private function hasAtLeastOneAccount(): bool
    {
        return ! empty(Account::first());
    }
}
