<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Turn the authenticator app off, and forget the secret it shares with us. The recovery codes
 * go too, unless a security key still answers the second step.
 */
class DisableTwoFactorAuthentication
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): User
    {
        DB::transaction(function (): void {
            $this->disable();
        });

        $this->log();

        return $this->user;
    }

    private function disable(): void
    {
        User::query()->whereKey($this->user->id)->lockForUpdate()->first();
        $this->user->refresh();

        $this->user->two_factor_secret = null;
        $this->user->two_factor_confirmed_at = null;

        if (! $this->user->securityKeys()->exists()) {
            $this->user->two_factor_recovery_codes = null;
        }

        $this->user->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: $this->user->usesTwoFactorAuthentication() ? UserActionEnum::AuthenticatorAppRemoved : UserActionEnum::TwoFactorDisabled,
        )->onQueue('low');
    }
}
