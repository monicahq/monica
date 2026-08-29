<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\EmailType;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Jobs\SendEmail;
use App\Mail\PasskeyAdded;
use App\Models\User;
use App\Models\WebauthnKey;
use LaravelWebauthn\Events\WebauthnRegister;

/**
 * Record and announce a passkey somebody just added.
 *
 * The email is not a courtesy. A passkey added to an account is a new way into
 * it, so the person who owns the address hears about it whether or not they are
 * the one who added it.
 */
class HandlePasskeyRegistration
{
    public function handle(WebauthnRegister $event): void
    {
        $passkey = $event->webauthnKey;

        if (! $passkey instanceof WebauthnKey) {
            return;
        }

        $user = $passkey->user;

        if (! $user instanceof User) {
            return;
        }

        LogUserAction::dispatch(
            account: $user->account,
            user: $user,
            action: UserActionEnum::PasskeyAdded,
            parameters: ['name' => $passkey->name],
            loggable: $passkey,
        )->onQueue('low');

        SendEmail::dispatch(
            mailable: new PasskeyAdded(name: $passkey->name),
            account: $user->account,
            emailType: EmailType::PasskeyAdded,
            user: $user,
        )->onQueue('high');
    }
}
