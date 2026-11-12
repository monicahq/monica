<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionMaintenanceTypeEnum;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Check what is said about a maintenance record and turn it into the columns that record it. Recording
 * and changing a record apply the same rules: a title is given, the job is not in the future, the next
 * due date does not come before the job, a cost is zero or more and has a currency, and text loses its
 * markup.
 */
class PossessionMaintenanceRecordAttributes
{
    /**
     * @return array{type: PossessionMaintenanceTypeEnum, title: string, performed_by: ?string, performed_on: CarbonImmutable, cost: ?string, currency: ?CurrencyEnum, next_due_on: ?CarbonImmutable, description: ?string}
     */
    public static function from(
        PossessionMaintenanceTypeEnum $type,
        string $title,
        CarbonImmutable $performedOn,
        CarbonImmutable $today,
        ?string $performedBy,
        ?string $cost,
        ?CurrencyEnum $currency,
        ?CarbonImmutable $nextDueOn,
        ?string $description,
    ): array {
        $title = TextSanitizer::plainText($title);

        if ($title === '') {
            throw ValidationException::withMessages(['title' => __('Say what was done.')]);
        }

        if ($performedOn->toDateString() > $today->toDateString()) {
            throw ValidationException::withMessages(['performed_on' => __('The job cannot be performed in the future.')]);
        }

        if ($nextDueOn !== null && $nextDueOn->toDateString() < $performedOn->toDateString()) {
            throw ValidationException::withMessages(['next_due_on' => __('The next due date cannot come before the job.')]);
        }

        $cost = $cost === null || mb_trim($cost) === '' ? null : mb_trim($cost);

        if ($cost !== null && (! is_numeric($cost) || (float) $cost < 0 || (float) $cost > (float) PossessionDeclaredValue::MAX_DECLARED_VALUE)) {
            throw ValidationException::withMessages(['cost' => __('Write an amount of zero or more.')]);
        }

        if ($cost !== null && $currency === null) {
            throw ValidationException::withMessages(['currency' => __('Choose the currency of the cost.')]);
        }

        return [
            'type' => $type,
            'title' => $title,
            'performed_by' => TextSanitizer::nullablePlainText($performedBy),
            'performed_on' => $performedOn->startOfDay(),
            'cost' => $cost === null ? null : number_format((float) $cost, 2, '.', ''),
            'currency' => $cost === null ? null : $currency,
            'next_due_on' => $nextDueOn?->startOfDay(),
            'description' => TextSanitizer::nullablePlainText($description),
        ];
    }
}
