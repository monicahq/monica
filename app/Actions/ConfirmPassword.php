<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Check the password somebody gives to prove they are the account holder before a sensitive action.
 * An account with no password cannot be proved this way and is refused without a hash comparison.
 */
class ConfirmPassword
{
    public function __construct(
        private readonly User $user,
        private readonly string $password,
    ) {}

    public function execute(): bool
    {
        $confirmed = $this->user->password !== null
            && Hash::check($this->password, $this->user->password);

        $this->log($confirmed);

        return $confirmed;
    }

    private function log(bool $confirmed): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: $confirmed
                ? UserActionEnum::PasswordConfirmed
                : UserActionEnum::PasswordConfirmationRefused,
        )->onQueue('low');
    }
}
