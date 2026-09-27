<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;

class ReminderWords
{
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
