<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Passkey;
use App\Models\User;
use App\Notifications\PasskeyAdded;
use Laravel\Passkeys\Events\PasskeyRegistered;

/**
 * Record and announce a passkey somebody just added.
 *
 * The email is not a courtesy. A passkey added to an account is a new way into
 * it, so the person who owns the address hears about it whether or not they are
 * the one who added it.
 */
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
            parameters: ['name' => $passkey->name],
            loggable: $passkey,
        )->onQueue('low');

        $user->notify(new PasskeyAdded(name: $passkey->name));
    }
}
