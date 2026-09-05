<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;

/**
 * Change the time zone a user reads dates in.
 */
class UpdateTimezone
{
    public function __construct(
        private readonly User $user,
        private readonly string $timezone,
    ) {}

    public function execute(): User
    {
        $this->update();
        $this->log();

        return $this->user;
    }

    private function update(): void
    {
        $this->user->timezone = $this->timezone;
        $this->user->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::UserTimezoneUpdate,
            parameters: ['name' => $this->timezone],
        )->onQueue('low');
    }
}
