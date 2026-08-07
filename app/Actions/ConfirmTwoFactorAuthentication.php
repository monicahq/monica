<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\RecoveryCodes;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

/**
 * Finish enrolling somebody in two factor authentication.
 *
 * The code they typed is checked against the secret EnableTwoFactorAuthentication
 * wrote down. Only if it matches is the account marked as protected, which is
 * what makes the challenge appear the next time they sign in. Checking first
 * means nobody can lock themselves out with an app that was never set up
 * properly.
 *
 * Passing also mints the recovery codes, since they are only worth having for
 * an account that can be locked out of.
 */
class ConfirmTwoFactorAuthentication
{
    private string $code;

    public function __construct(
        private readonly User $user,
        string $code,
    ) {
        $this->code = TextSanitizer::plainText($code);
    }

    public function execute(): bool
    {
        if (! $this->verify()) {
            return false;
        }

        $this->confirm();
        $this->log();

        return true;
    }

    private function verify(): bool
    {
        if ($this->user->two_factor_secret === null) {
            return false;
        }

        return (bool) new Google2FA()->verifyKey($this->user->two_factor_secret, $this->code);
    }

    private function confirm(): void
    {
        $this->user->two_factor_confirmed_at = now();
        $this->user->two_factor_recovery_codes = RecoveryCodes::generate();
        $this->user->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->user->company,
            user: $this->user,
            action: UserActionEnum::TwoFactorEnabled,
        )->onQueue('low');
    }
}
