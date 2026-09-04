<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\PasskeyAvailability;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Notifications\PasswordRemoved;
use Illuminate\Validation\ValidationException;

/**
 * Give up a password in favour of passkeys.
 *
 * This is the only thing in the feature that takes a way in away rather than
 * adding one, so it refuses in three cases rather than trusting the screen.
 *
 * Without a passkey there would be nothing left but an emailed link, which is
 * not a credential somebody chose. On an instance where passkeys cannot be used
 * the passkeys they hold are unusable, so removing the password would strand
 * them; a disabled button is not a refusal, and this is where the refusal lives.
 * And an SSO account has no password to remove in the first place.
 */
class DestroyUserPassword
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): User
    {
        $this->validate();
        $this->remove();
        $this->log();
        $this->notify();

        return $this->user;
    }

    private function validate(): void
    {
        if ($this->user->usesSingleSignOn()) {
            throw ValidationException::withMessages([
                'password' => __('This account signs in through an identity provider and has no password.'),
            ]);
        }

        if ($this->user->password === null) {
            throw ValidationException::withMessages([
                'password' => __('This account has no password to remove.'),
            ]);
        }

        if (! $this->user->hasPasskeys()) {
            throw ValidationException::withMessages([
                'password' => __('Add a passkey first. Removing your password without one would leave nothing but a link sent by email.'),
            ]);
        }

        if (! PasskeyAvailability::onThisInstance()) {
            throw ValidationException::withMessages([
                'password' => __('Passkeys cannot be used on this instance, so your password is what signs you in. It cannot be removed here.'),
            ]);
        }
    }

    private function remove(): void
    {
        $this->user->password = null;
        $this->user->password_changed_at = null;
        $this->user->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::UserPasswordRemoved,
        )->onQueue('low');
    }

    private function notify(): void
    {
        $this->user->notify(new PasswordRemoved);
    }
}
