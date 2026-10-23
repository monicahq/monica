<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Passkey;
use App\Models\User;
use App\Notifications\PasskeyAdded;
use Laravel\Passkeys\Events\PasskeyRegistered;

class HandlePasskeyRegistration
{
    public function handle(PasskeyRegistered $event): void
    {
        $passkey = $event->passkey;
        $user = $event->user;

        if (! $passkey instanceof Passkey || ! $user instanceof User) {
            return;
        }

        LogUserAction::dispatch(
            account: $user->account,
            user: $user,
            action: UserActionEnum::PasskeyAdded,
            parameters: ['name' => $passkey->displayName()],
            loggable: $passkey,
        )->onQueue('low');

        $user->notify(new PasskeyAdded(name: $passkey->displayName()));
    }
}
