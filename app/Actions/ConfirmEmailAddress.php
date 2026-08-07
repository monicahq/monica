<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;

/**
 * Record that a user proved the email address they signed up with is theirs.
 */
class ConfirmEmailAddress
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): User
    {
        $this->confirm();
        $this->log();

        return $this->user;
    }

    private function confirm(): void
    {
        $this->user->email_verified_at = now();
        $this->user->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->user->company,
            user: $this->user,
            action: UserActionEnum::EmailConfirmation,
        )->onQueue('low');
    }
}
