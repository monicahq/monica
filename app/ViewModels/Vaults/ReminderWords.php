<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;
use App\Helpers\ReminderSchedule;

/**
 * The two sentences a reminder is described by: when in relation to its date it
 * fires, and how often it comes round.
 *
 * It sits beside the view models rather than inside either of the two that need
 * it, because both {@see ContactReminderBlock}, which explains the rule under
 * the date it reads, and {@see ReminderViewModel}, which explains it on its own
 * screen, have to say the same thing. Two copies of these would be two copies of
 * the plural rules underneath them, and those are exactly what goes stale: the
 * pinned wordings, the zero guard and the placeholder in every segment are all
 * enforced by `monica:localize --check`, which reads each call rather than the
 * pair of them.
 *
 * Everything here is words. No date arithmetic happens in this class, because
 * that is {@see ReminderSchedule}, which a queued job also reads.
 */
class ReminderWords
{
    /**
     * One moment in words.
     *
     * The span and the side it falls on are two phrases rather than one, so the
     * span can be a counted phrase and the language can decide its own wording
     * per number. Assembling them is what lets the browser write the same
     * sentence from the same pieces while somebody is typing.
     */
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

    /**
     * How often the rule fires, in words.
     *
     * Each wording is pinned at one, because "every year" reads better than
     * "every 1 year" and the number is already known there. A pinned wording has
     * to keep zero away from it, which is what the guard below is for.
     */
    public static function cadence(ReminderCadenceEnum $cadence, int $interval): string
    {
        if (! $cadence->recurs()) {
            return __('once and no more');
        }

        /* Zero has to be kept away from a wording pinned to one, because several
           languages group it with the singular and would read "every year" for
           a rhythm of none. Nothing writes a rule with an interval of zero, and
           a row holding one describes no rhythm at all. */
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
