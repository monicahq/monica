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
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TwoFactorEnabled,
        )->onQueue('low');
    }
}
