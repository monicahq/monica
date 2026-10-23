<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Passkey;
use App\Models\User;
use App\Notifications\PasskeyRemoved;
use Laravel\Passkeys\Events\PasskeyDeleted;

class HandlePasskeyRemoval
{
    public function handle(PasskeyDeleted $event): void
    {
        $passkey = $event->passkey;
        $user = $event->user;

        if (! $passkey instanceof Passkey || ! $user instanceof User) {
            return;
        }

        LogUserAction::dispatch(
            account: $user->account,
            user: $user,
            action: UserActionEnum::PasskeyRemoved,
            parameters: ['name' => $passkey->displayName()],
        )->onQueue('low');

        $user->notify(new PasskeyRemoved(name: $passkey->displayName()));
    }
}
