<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DateFormatEnum;
use App\Enums\TimeFormatEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;

/**
 * Change what somebody chose about the way the application reads to them: the
 * language of the interface, the clock times are written on, and the order
 * dates read in.
 *
 * These belong to the account rather than to the browser, so they follow the
 * person to every device they sign in from.
 *
 * A null date format is a choice rather than an omission: it means dates follow
 * whatever the interface language writes them as.
 */
class UpdatePreferences
{
    public function __construct(
        private readonly User $user,
        private readonly string $locale,
        private readonly TimeFormatEnum $timeFormat,
        private readonly ?DateFormatEnum $dateFormat = null,
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
        $this->user->date_format = $this->dateFormat;
        $this->user->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::UserPreferencesUpdate,
        )->onQueue('low');
    }
}
