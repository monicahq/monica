<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\DateFormatEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\NumberFormatEnum;
use App\Models\FieldValue;
use App\Models\User;
use App\Models\ValueListItem;
use Carbon\CarbonInterface;

/**
 * Turns what somebody typed into what a page shows them.
 *
 * Reading a value is not reading a column. A choice is stored as the item it
 * points at and read as the label that item carries; a date is stored one way
 * and read in whichever order the person looking at it reads dates; a number is
 * stored exactly and read with the separators they use. None of that belongs to
 * the value, and none of it belongs to one screen either, which is why it lives
 * here: the page that shows a value and the form that edits it have to agree
 * about what it says.
 *
 * A value with nothing in it reads as null rather than as an empty string, so
 * every screen keeps its own way of saying that nothing has been recorded.
 */
class FieldValueFormatter
{
    public function __construct(private readonly User $user) {}

    /**
     * What the value reads as, or null when it holds nothing.
     */
    public function format(FieldValue $value): ?string
    {
        return match ($value->field->type) {
            FieldTypeEnum::Text, FieldTypeEnum::Url, FieldTypeEnum::Email,
            FieldTypeEnum::Phone => $this->blankToNull($value->value_string),
            FieldTypeEnum::Textarea => $this->blankToNull($value->value_text),
            FieldTypeEnum::Number => $this->number($value),
            FieldTypeEnum::Boolean => $this->boolean($value),
            FieldTypeEnum::Date => $this->date($value),
            FieldTypeEnum::RecurringDate => $this->recurringDate($value),
            FieldTypeEnum::Select, FieldTypeEnum::Radio => $value->valueListItem?->label,
            FieldTypeEnum::Multiselect => $this->choices($value),
            FieldTypeEnum::File, FieldTypeEnum::Image,
            FieldTypeEnum::Avatar => $this->blankToNull($value->file?->original_name),
            default => null,
        };
    }

    private function blankToNull(?string $value): ?string
    {
        return $value === null || mb_trim($value) === '' ? null : $value;
    }

    /**
     * The number with the separators the person reads, and with as many
     * decimals as were actually written. The column keeps six of them, and
     * rendering a shoe size as "42.000000" would be reporting the precision of
     * the storage rather than of the answer.
     */
    private function number(FieldValue $value): ?string
    {
        if ($value->value_number === null) {
            return null;
        }

        $decimals = 0;

        if (str_contains($value->value_number, '.')) {
            $decimals = mb_strlen(rtrim(explode('.', $value->value_number)[1], '0'));
        }

        return $this->numberFormat()->format((float) $value->value_number, $decimals);
    }

    private function boolean(FieldValue $value): ?string
    {
        if ($value->value_boolean === null) {
            return null;
        }

        return $value->value_boolean ? __('Yes') : __('No');
    }

    private function date(FieldValue $value): ?string
    {
        return $value->value_date === null ? null : $this->write($value->value_date, $this->dateFormat());
    }

    /**
     * A date that comes round again, with the parts nobody knows left out.
     *
     * The year is stored as a placeholder when it is unknown, and this is the
     * promise that it never reaches a screen: the token is taken out of the
     * format rather than the year being replaced by something, so no page can
     * render 1900 and no reader can mistake it for a fact. A date nobody knows
     * the month of either says nothing at all, and reads as nothing.
     */
    private function recurringDate(FieldValue $value): ?string
    {
        if ($value->value_date === null) {
            return null;
        }

        if ($value->month_is_unknown) {
            return $value->year_is_unknown ? null : $this->write($value->value_date, 'Y');
        }

        if ($value->year_is_unknown) {
            return $this->write($value->value_date, $this->withoutTheYear($this->dateFormat()));
        }

        return $this->write($value->value_date, $this->dateFormat());
    }

    /**
     * The same format with the year taken out of it, and with the separator it
     * left behind taken out too, so `d/m/Y` reads `d/m` rather than `d/m/`.
     */
    private function withoutTheYear(string $format): string
    {
        return mb_trim((string) preg_replace('#[/.\-\s]*Y[/.\-\s]*#', ' ', $format));
    }

    /**
     * Every item a multiple choice points at, in the order the list offers
     * them, so two records answering the same question read the same way round.
     */
    private function choices(FieldValue $value): ?string
    {
        $ids = $value->value_list_item_ids ?? [];

        if ($ids === []) {
            return null;
        }

        $labels = ValueListItem::query()
            ->whereIn('id', $ids)
            ->orderBy('position')
            ->get()
            ->map(fn (ValueListItem $item): string => $item->label);

        return $labels->isEmpty() ? null : $labels->implode(__(', '));
    }

    private function write(CarbonInterface $date, string $format): string
    {
        return DateFormatEnum::write($date, $format);
    }

    /**
     * The order this person reads dates in. Somebody who has never chosen reads
     * them the way their language does, which is what the locale carries.
     */
    private function dateFormat(): string
    {
        return $this->user->date_format->value
            ?? (string) config('monica.locales.'.app()->getLocale().'.date_format', DateFormatEnum::DayFirst->value);
    }

    private function numberFormat(): NumberFormatEnum
    {
        return $this->user->number_format
            ?? NumberFormatEnum::tryFrom((string) config('monica.locales.'.app()->getLocale().'.number_format'))
            ?? NumberFormatEnum::CommaAndDot;
    }
}
