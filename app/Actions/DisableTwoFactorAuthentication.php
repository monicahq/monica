<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;

/**
 * Turn two factor authentication off, and forget everything it needed: the
 * secret the authenticator app shares with us, and the recovery codes.
 *
 * Nothing is kept, so turning it on again starts from a new secret and the app
 * has to be set up afresh. The entry left in the logs is the only trace.
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
            company: $this->user->company,
            user: $this->user,
            action: UserActionEnum::TwoFactorDisabled,
        )->onQueue('low');
    }
}
