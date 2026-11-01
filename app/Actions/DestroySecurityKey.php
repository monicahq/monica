<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\SecurityKey;
use App\Models\User;
use App\Notifications\SecurityKeyRemoved;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Remove a security key. Removing the last second factor on an account turns
 * two factor authentication off, and the recovery codes stop working with it.
 */
class DestroySecurityKey
{
    private bool $turnsTwoFactorOff = false;

    public function __construct(
        private readonly User $user,
        private readonly SecurityKey $securityKey,
    ) {}

    public function execute(): void
    {
        $this->validate();

        DB::transaction(function (): void {
            $this->destroy();
        });

        $this->log();
        $this->user->notify(new SecurityKeyRemoved(name: $this->securityKey->name));
    }

    private function validate(): void
    {
        if ($this->securityKey->user_id !== $this->user->id) {
            throw new ModelNotFoundException('Security key not found');
        }
    }

    private function destroy(): void
    {
        User::query()->whereKey($this->user->id)->lockForUpdate()->first();
        $this->user->refresh();

        $this->securityKey->delete();

        $this->turnsTwoFactorOff = ! $this->user->usesTwoFactorAuthentication();

        if ($this->turnsTwoFactorOff) {
            $this->user->two_factor_recovery_codes = null;
            $this->user->save();
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SecurityKeyRemoved,
            parameters: ['name' => $this->securityKey->name],
        );

        if (! $this->turnsTwoFactorOff) {
            return;
        }

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TwoFactorDisabled,
        );
    }
}
