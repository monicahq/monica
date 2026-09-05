<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\UserActionEnum;
use App\Jobs\CheckLastLogin;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Notifications\NewLoginDetected;
use Laravel\Passkeys\Events\PasskeyVerified;

/**
 * Everything this application does once a passkey has signed somebody in.
 *
 * It is the same list a password sign-in goes through, which lives in
 * AttemptLogin: stamp the moment, look at where it came from, write it down, and
 * tell the owner of the address. A passkey sign-in earns the notification for
 * the same reason a magic link does: nothing was typed, so nothing about it
 * would look unusual to the person it happened to.
 *
 * The activity entry names which passkey was used, because an account with
 * several of them is the normal case and "signed in" alone would not say enough.
 * The event carries it, so nothing has to be guessed at.
 */
class HandlePasskeyLogin
{
    public function handle(PasskeyVerified $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
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

        $user->notify(new NewLoginDetected(ip: request()->ip() ?? ''));
    }
}
