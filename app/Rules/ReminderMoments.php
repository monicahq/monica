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

class ReminderMoments implements ValidationRule
{
    /** @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail */
    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $problem = self::problemWith($value);

        if ($problem !== null) {
            $fail($problem);
        }
    }

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
                return __('Two of the moments are the same, so one of them would tell you nothing new.');
            }

            $seen[] = $key;
        }

        return null;
    }

    /**
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

    /** @param  array<array-key, mixed>  $moment */
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

        if ((int) $amount < Reminder::MINIMUM_AMOUNT || (int) $amount > $unit->maximumAmount()) {
            return __('A moment has to be between :min and :max away from the date.', [
                'min' => Reminder::MINIMUM_AMOUNT,
                'max' => $unit->maximumAmount(),
            ]);
        }

        return null;
    }

    /** @param  array<array-key, mixed>  $moment */
    private static function keyOf(ReminderMomentDirectionEnum $direction, array $moment): string
    {
        if (! $direction->needsAnAmount()) {
            return $direction->value;
        }

        return $direction->value.':'.(int) $moment['amount'].':'.(string) $moment['unit'];
    }
}
