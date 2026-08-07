<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TimeFormatEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;

/**
 * Change what somebody chose about the way the application reads to them: the
 * language of the interface, and the clock times are written on.
 *
 * These belong to the account rather than to the browser, so they follow the
 * person to every device they sign in from.
 */
class UpdatePreferences
{
    public function __construct(
        private readonly User $user,
        private readonly string $locale,
        private readonly TimeFormatEnum $timeFormat,
    ) {}

    public function execute(): User
    {
        $this->update();
        $this->log();

        return $this->user;
    }

    private function update(): void
    {
        $this->user->locale = $this->locale;
        $this->user->time_format = $this->timeFormat;
        $this->user->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->user->company,
            user: $this->user,
            action: UserActionEnum::UserPreferencesUpdate,
        )->onQueue('low');
    }
}
