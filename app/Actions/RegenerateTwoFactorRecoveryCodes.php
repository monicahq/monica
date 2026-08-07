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
 *
 * Somebody who has spent most of theirs, or who thinks the list was seen by
 * somebody else, asks for this. Every code that was there before stops working
 * the moment it runs, which is the point of it.
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

    /**
     * Recovery codes only mean anything to an account that answers a challenge,
     * so an account that does not is treated as having nothing to regenerate.
     */
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
            company: $this->user->company,
            user: $this->user,
            action: UserActionEnum::TwoFactorRecoveryCodesRegenerated,
        )->onQueue('low');
    }
}
