<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\UserActionEnum;
use App\Jobs\CheckLastLogin;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Notifications\NewLoginDetected;
use Laravel\Passkeys\Events\PasskeyVerified;

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
