<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DateFormatEnum;
use App\Enums\NumberFormatEnum;
use App\Enums\TimeFormatEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Change what somebody chose about the way the application reads to them: the language of the
 * interface, the clock times are written on, the order dates read in, and the separators numbers
 * are written with.
 */
class UpdatePreferences
{
    public function __construct(
        private readonly User $user,
        private readonly string $locale,
        private readonly TimeFormatEnum $timeFormat,
        private readonly ?DateFormatEnum $dateFormat = null,
        private readonly ?NumberFormatEnum $numberFormat = null,
    ) {}

    public function execute(): User
    {
        DB::transaction(function (): void {
            $this->update();
            $this->reorder();
        });

        $this->log();

        return $this->user;
    }

    private function reorder(): void
    {
        (new RebuildOrderingKeys(
            account: $this->user->account,
            locale: $this->locale,
        ))->execute();
    }

    private function update(): void
    {
        $this->user->locale = $this->locale;
        $this->user->time_format = $this->timeFormat;
        $this->user->date_format = $this->dateFormat;
        $this->user->number_format = $this->numberFormat;
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
