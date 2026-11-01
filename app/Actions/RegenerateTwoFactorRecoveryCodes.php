<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\RecoveryCodes;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Mint a fresh set of recovery codes, and throw the old ones away.
 */
class RegenerateTwoFactorRecoveryCodes
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): User
    {
        $this->validate();
        $this->regenerate();
        $this->log();

        return $this->user;
    }

    private function validate(): void
    {
        if (! $this->user->usesTwoFactorAuthentication()) {
            throw new ModelNotFoundException('Two factor authentication is not in use');
        }
    }

    private function regenerate(): void
    {
        $this->user->two_factor_recovery_codes = RecoveryCodes::generate();
        $this->user->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TwoFactorRecoveryCodesRegenerated,
        );
    }
}
