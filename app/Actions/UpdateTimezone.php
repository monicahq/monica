<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;

/**
 * Change the time zone a user reads dates in. It belongs to the account rather
 * than to the browser, so it follows them to every device they sign in from.
 *
 * One column, and deliberately nothing else. Changing a zone never rewrites
 * stored data and never shifts anything already computed: it decides how a
 * moment is written from here on, and a reminder rule freezes the zone it was
 * created in rather than following this one.
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
