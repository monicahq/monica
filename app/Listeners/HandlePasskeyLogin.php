<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\SignInMethodEnum;
use App\Enums\UserActionEnum;
use App\Jobs\CheckLastLogin;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Notifications\NewLoginDetected;
use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Events\PasskeyVerified;

/**
 * One event serves two ceremonies. Signing in and confirming who you are both run VerifyPasskey, so
 * this tells them apart by whether the guard already holds somebody: the login route is behind
 * `guest` and verifies before it logs anybody in, the confirmation route is behind `auth`.
 */
class HandlePasskeyLogin
{
    public function handle(PasskeyVerified $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        if (Auth::guard('web')->check()) {
            $this->recordConfirmation($user, $event);

            return;
        }

        $user->last_login_at = now();
        $user->save();

        CheckLastLogin::dispatch(
            user: $user,
            ip: request()->ip() ?? '',
        )->onQueue('low');

        LogUserAction::dispatch(
            account: $user->account,
            user: $user,
            action: UserActionEnum::PasskeyLogin,
            parameters: ['name' => $event->passkey->name],
            loggable: $event->passkey,
        )->onQueue('low');

        $user->notify(new NewLoginDetected(
            ip: request()->ip() ?? '',
            method: SignInMethodEnum::Passkey,
        ));
    }

    private function recordConfirmation(User $user, PasskeyVerified $event): void
    {
        LogUserAction::dispatch(
            account: $user->account,
            user: $user,
            action: UserActionEnum::PasskeyConfirmed,
            parameters: ['name' => $event->passkey->name],
            loggable: $event->passkey,
        )->onQueue('low');
    }
}
