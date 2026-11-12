<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionTransactionTypeEnum;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Check what is said about a transaction and turn it into the columns that record it. Recording and
 * changing a transaction apply the same rules: every figure is zero or more, a figure needs the
 * currency it is in, a source link is a full web address, and text loses its markup.
 */
class PossessionTransactionAttributes
{
    /**
     * @param  array{amount: ?string, tax: ?string, fees: ?string, shipping: ?string, entered_total: ?string}  $figures
     * @return array{type: PossessionTransactionTypeEnum, occurred_on: ?CarbonImmutable, counterparty: ?string, amount: ?string, tax: ?string, fees: ?string, shipping: ?string, entered_total: ?string, currency: ?CurrencyEnum, reference: ?string, source_url: ?string, note: ?string}
     */
    public static function from(
        PossessionTransactionTypeEnum $type,
        ?CarbonImmutable $occurredOn,
        ?string $counterparty,
        array $figures,
        ?CurrencyEnum $currency,
        ?string $reference,
        ?string $sourceUrl,
        ?string $note,
    ): array {
        $written = [];

        foreach ($figures as $column => $figure) {
            $figure = $figure === null || mb_trim($figure) === '' ? null : mb_trim($figure);

            if ($figure !== null && (! is_numeric($figure) || (float) $figure < 0 || (float) $figure > (float) PossessionDeclaredValue::MAX_DECLARED_VALUE)) {
                throw ValidationException::withMessages([$column => __('Write an amount of zero or more.')]);
            }

            $written[$column] = $figure === null ? null : number_format((float) $figure, 2, '.', '');
        }

        if ($currency === null && array_filter($written, fn (?string $figure): bool => $figure !== null) !== []) {
            throw ValidationException::withMessages(['currency' => __('Choose the currency of these amounts.')]);
        }

        $sourceUrl = $sourceUrl === null || mb_trim($sourceUrl) === '' ? null : mb_trim($sourceUrl);

        if ($sourceUrl !== null && filter_var($sourceUrl, FILTER_VALIDATE_URL) === false) {
            throw ValidationException::withMessages(['source_url' => __('Write a full web address, starting with https://.')]);
        }

        return [
            'type' => $type,
            'occurred_on' => $occurredOn?->startOfDay(),
            'counterparty' => TextSanitizer::nullablePlainText($counterparty),
            'amount' => $written['amount'],
            'tax' => $written['tax'],
            'fees' => $written['fees'],
            'shipping' => $written['shipping'],
            'entered_total' => $written['entered_total'],
            'currency' => $currency,
            'reference' => TextSanitizer::nullablePlainText($reference),
            'source_url' => $sourceUrl,
            'note' => TextSanitizer::nullablePlainText($note),
        ];
    }
}
