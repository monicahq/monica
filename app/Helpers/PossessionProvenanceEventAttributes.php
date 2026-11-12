<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionProvenanceEventTypeEnum;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Check what is said about a provenance event and turn it into the columns that record it. Recording
 * and changing an event apply the same rules: the date matches its precision, a source link is a full
 * web address, a linked transaction belongs to the same possession, and text loses its markup.
 */
class PossessionProvenanceEventAttributes
{
    /**
     * @return array{possession_transaction_id: ?int, type: PossessionProvenanceEventTypeEnum, title: ?string, date_precision: PossessionProvenanceDatePrecisionEnum, occurred_on: ?CarbonImmutable, from: ?string, to: ?string, location: ?string, description: ?string, reference_number: ?string, source_url: ?string, is_verified: bool}
     */
    public static function from(
        Possession $possession,
        PossessionProvenanceEventTypeEnum $type,
        PossessionProvenanceDatePrecisionEnum $datePrecision,
        ?string $occurredOn,
        ?string $title,
        ?string $from,
        ?string $to,
        ?string $location,
        ?string $description,
        ?string $referenceNumber,
        ?string $sourceUrl,
        ?PossessionTransaction $transaction,
        bool $isVerified,
    ): array {
        $date = $datePrecision->parse($occurredOn);

        if ($date === false) {
            throw ValidationException::withMessages(['occurred_on' => __('Write the date in the shape its precision asks for.')]);
        }

        $sourceUrl = $sourceUrl === null || mb_trim($sourceUrl) === '' ? null : mb_trim($sourceUrl);

        if ($sourceUrl !== null && filter_var($sourceUrl, FILTER_VALIDATE_URL) === false) {
            throw ValidationException::withMessages(['source_url' => __('Write a full web address, starting with https://.')]);
        }

        if ($transaction !== null && $transaction->possession_id !== $possession->id) {
            throw ValidationException::withMessages(['possession_transaction_id' => __('Choose a transaction of this possession.')]);
        }

        return [
            'possession_transaction_id' => $transaction?->id,
            'type' => $type,
            'title' => TextSanitizer::nullablePlainText($title),
            'date_precision' => $datePrecision,
            'occurred_on' => $date,
            'from' => TextSanitizer::nullablePlainText($from),
            'to' => TextSanitizer::nullablePlainText($to),
            'location' => TextSanitizer::nullablePlainText($location),
            'description' => TextSanitizer::nullablePlainText($description),
            'reference_number' => TextSanitizer::nullablePlainText($referenceNumber),
            'source_url' => $sourceUrl,
            'is_verified' => $isVerified,
        ];
    }
}
