<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\EmailType;
use App\Enums\UserActionEnum;
use App\Jobs\CheckLastLogin;
use App\Jobs\LogUserAction;
use App\Jobs\SendEmail;
use App\Mail\NewLoginDetected;
use App\Models\User;
use App\Models\WebauthnKey;
use LaravelWebauthn\Events\WebauthnLogin;

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
 */
class HandlePasskeyLogin
{
    public function handle(WebauthnLogin $event): void
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
            parameters: ['name' => $this->passkeyName($user)],
        )->onQueue('low');

        SendEmail::dispatch(
            mailable: new NewLoginDetected(ip: request()->ip() ?? ''),
            account: $user->account,
            emailType: EmailType::NewLogin,
            user: $user,
        )->onQueue('high');
    }

    /**
     * Which passkey just let them in. The event does not carry it, so it is read
     * back as the one used most recently, which RecordPasskeyUse has just
     * stamped. A passkey removed between the two is not worth guarding against:
     * the name is for a sentence in a log, not for a decision.
     */
    private function passkeyName(User $user): string
    {
        return WebauthnKey::query()
            ->where('user_id', $user->id)
            ->orderByDesc('used_at')
            ->value('name') ?? __('a passkey');
    }
}
