<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Passkey;
use App\Models\User;
use App\Notifications\PasskeyRemoved;
use Laravel\Passkeys\Events\PasskeyDeleted;

/**
 * Record and announce a passkey somebody just revoked.
 *
 * The email is not a courtesy, for the same reason the one announcing a new
 * passkey is not: a way into the account has changed, and the person who owns
 * the address hears about it whether or not they are the one who did it.
 *
 * The event fires after the row is gone, which is why the activity entry takes
 * the name from the event rather than reading it back. `LogUserAction` accepts
 * what it is about in its constructor precisely so a queued job never has to go
 * looking for a record that has been deleted.
 */
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
            parameters: ['name' => $passkey->name],
        )->onQueue('low');

        $user->notify(new PasskeyRemoved(name: $passkey->name));
    }
}
