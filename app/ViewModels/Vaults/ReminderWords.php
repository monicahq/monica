<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;

class ReminderWords
{
    public static function moment(ReminderMomentDirectionEnum $direction, ?int $amount, ?ReminderMomentUnitEnum $unit): string
    {
        if (! $direction->needsAnAmount() || $amount === null || $unit === null) {
            return __('on the day');
        }

        $span = match ($unit) {
            ReminderMomentUnitEnum::Days => trans_choice(':count day|:count days', $amount),
            ReminderMomentUnitEnum::Weeks => trans_choice(':count week|:count weeks', $amount),
            ReminderMomentUnitEnum::Months => trans_choice(':count month|:count months', $amount),
        };

        return $direction === ReminderMomentDirectionEnum::Before
            ? __(':span before', ['span' => $span])
            : __(':span after', ['span' => $span]);
    }

    public static function cadence(ReminderCadenceEnum $cadence, int $interval): string
    {
        if (! $cadence->recurs()) {
            return __('once and no more');
        }

        if ($interval === 0) {
            return '';
        }

        return match ($cadence) {
            ReminderCadenceEnum::Weekly => trans_choice('{1}every week|every :count weeks', $interval),
            ReminderCadenceEnum::Monthly => trans_choice('{1}every month|every :count months', $interval),
            default => trans_choice('{1}every year|every :count years', $interval),
        };
    }
}
