<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DateFormatEnum;
use App\Enums\NumberFormatEnum;
use App\Enums\TimeFormatEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\LifeEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Change what somebody chose about the way the application reads to them: the language of the
 * interface, the clock times are written on, the order dates read in, the separators numbers are
 * written with, and whether addresses name the vault and the contact they open.
 */
class UpdatePreferences
{
    private bool $rewritesTheRecords = false;

    public function __construct(
        private readonly User $user,
        private readonly string $locale,
        private readonly TimeFormatEnum $timeFormat,
        private readonly ?DateFormatEnum $dateFormat = null,
        private readonly ?NumberFormatEnum $numberFormat = null,
        private readonly bool $usesReadableAddresses = true,
    ) {}

    public function execute(): User
    {
        DB::transaction(function (): void {
            $this->update();
            $this->reorder();
        });

        $this->resendTheAccountsRecords();
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
        $this->user->uses_readable_addresses = $this->usesReadableAddresses;
        $this->user->save();

        $this->rewritesTheRecords = $this->user->wasChanged(['locale', 'date_format', 'number_format']);
    }

    /**
     * Search holds every value as the owner reads it: another language or another way of writing a
     * date or a number is another text to be found by.
     */
    private function resendTheAccountsRecords(): void
    {
        if (! $this->rewritesTheRecords) {
            return;
        }

        Contact::sendToSearch(Contact::query()->where('account_id', $this->user->account_id));
        Entity::sendToSearch(Entity::query()->where('account_id', $this->user->account_id));
        LifeEvent::sendToSearch(LifeEvent::query()->where('account_id', $this->user->account_id));
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
