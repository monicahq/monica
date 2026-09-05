<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;

/**
 * Turn two factor authentication off, and forget everything it needed: the secret the
 * authenticator app shares with us, and the recovery codes.
 */
class DisableTwoFactorAuthentication
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): User
    {
        $this->disable();
        $this->log();

        return $this->user;
    }

    private function disable(): void
    {
        $this->user->two_factor_secret = null;
        $this->user->two_factor_confirmed_at = null;
        $this->user->two_factor_recovery_codes = null;
        $this->user->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TwoFactorDisabled,
        )->onQueue('low');
    }
}
