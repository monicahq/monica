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
 * Change what somebody chose about the way the application reads to them: the
 * language of the interface, the clock times are written on, the order dates
 * read in, and the separators numbers are written with.
 *
 * These belong to the account rather than to the browser, so they follow the
 * person to every device they sign in from.
 *
 * A null date or number format is a choice rather than an omission: it means
 * those follow whatever the interface language writes them as.
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

    /**
     * The keys every list is ordered on describe the language chosen before this,
     * so they are written again in the same transaction as the choice. A list
     * ordered on keys half in one language and half in another can show a row
     * twice or miss it. If this cannot finish, the language stays as it was.
     */
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
