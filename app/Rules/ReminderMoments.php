<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;
use App\Models\Reminder;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Override;

/**
 * What a valid set of moments is, and what it looks like once it is stored.
 *
 * A rule with no moment says nothing, so at least one is required. A moment on
 * the day itself carries no amount and no unit, and one before or after carries
 * both. Two identical moments are refused because the second can never say
 * anything the first has not, and somebody who added one twice by accident would
 * otherwise be told about the same day twice for ever.
 *
 * How far a moment may reach depends on what it counts in. A year of days, a
 * year of weeks and a year of months are three different numbers, and one
 * ceiling for all three is what let "365 months before" through.
 *
 * It refuses rather than tidies. Dropping a duplicate or filling in a missing
 * unit would be deciding on somebody's behalf what they meant.
 *
 * Both halves are static so the form and the action behind it run the same code.
 * The form is where somebody reads the message; the action is the backstop for
 * every other caller, and neither is allowed to have its own opinion about what
 * a moment is.
 */
class ReminderMoments implements ValidationRule
{
    /**
     * @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail
     */
    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $problem = self::problemWith($value);

        if ($problem !== null) {
            $fail($problem);
        }
    }

    /**
     * What is wrong with a set of moments, or null when nothing is.
     */
    public static function problemWith(mixed $value): ?string
    {
        if (! is_array($value) || $value === []) {
            return __('A reminder needs at least one moment to tell you about.');
        }

        $seen = [];

        foreach ($value as $moment) {
            if (! is_array($moment)) {
                return __('One of the moments is not readable.');
            }

            $direction = ReminderMomentDirectionEnum::tryFrom((string) ($moment['direction'] ?? ''));

            if ($direction === null) {
                return __('A moment has to fall on the day, before it or after it.');
            }

            $problem = self::problemWithTheAmount($direction, $moment);

            if ($problem !== null) {
                return $problem;
            }

            $key = self::keyOf($direction, $moment);

            if (in_array($key, $seen, true)) {
                return __('Two of the moments are the same, so one of them would never say anything new.');
            }

            $seen[] = $key;
        }

        return null;
    }

    /**
     * The set of moments as it is stored: the amount and the unit dropped from
     * a moment that falls on the day itself, and the amount read as a number.
     *
     * The form sends an amount for every row, because the one it hides is still
     * an input somebody typed in before switching the row to the day itself.
     * Storing it would be storing a setting the direction cannot honour.
     *
     * @param  array<array-key, mixed>  $value
     * @return list<array{direction: string, amount: int|null, unit: string|null}>
     */
    public static function normalise(array $value): array
    {
        $moments = [];

        foreach ($value as $moment) {
            $direction = ReminderMomentDirectionEnum::from((string) $moment['direction']);
            $carries = $direction->needsAnAmount();

            $moments[] = [
                'direction' => $direction->value,
                'amount' => $carries ? (int) $moment['amount'] : null,
                'unit' => $carries ? ReminderMomentUnitEnum::from((string) $moment['unit'])->value : null,
            ];
        }

        return $moments;
    }

    /**
     * @param  array<array-key, mixed>  $moment
     */
    private static function problemWithTheAmount(ReminderMomentDirectionEnum $direction, array $moment): ?string
    {
        if (! $direction->needsAnAmount()) {
            return null;
        }

        $amount = $moment['amount'] ?? null;

        if (! is_numeric($amount) || (int) $amount != $amount) {
            return __('A moment before or after the date has to say how much.');
        }

        $unit = ReminderMomentUnitEnum::tryFrom((string) ($moment['unit'] ?? ''));

        if ($unit === null) {
            return __('A moment has to be counted in days, weeks or months.');
        }

        /* The ceiling belongs to the unit. One number for all three is what let
           "365 months before" through, which is a reminder about a different
           date entirely. */
        if ((int) $amount < Reminder::MINIMUM_AMOUNT || (int) $amount > $unit->maximumAmount()) {
            /* The unit is not named in the sentence. It is chosen in the control
               beside the number, and a noun interpolated into a sentence needs a
               case ending in several of the languages this ships in. */
            return __('A moment has to be between :min and :max away from the date.', [
                'min' => Reminder::MINIMUM_AMOUNT,
                'max' => $unit->maximumAmount(),
            ]);
        }

        return null;
    }

    /**
     * @param  array<array-key, mixed>  $moment
     */
    private static function keyOf(ReminderMomentDirectionEnum $direction, array $moment): string
    {
        if (! $direction->needsAnAmount()) {
            return $direction->value;
        }

        return $direction->value.':'.(int) $moment['amount'].':'.(string) $moment['unit'];
    }
}
