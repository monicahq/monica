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

class FieldValueFormatter
{
    public function __construct(private readonly User $user) {}

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
            FieldTypeEnum::Address => $this->address($value),
            default => null,
        };
    }

    private function address(FieldValue $value): ?string
    {
        if (! is_array($value->value_json)) {
            return null;
        }

        $parts = array_filter(array_map(
            fn (string $part): string => mb_trim((string) ($value->value_json[$part] ?? '')),
            ['po_box', 'extended', 'street', 'city', 'region', 'postal_code', 'country'],
        ));

        return $parts === [] ? null : implode(', ', $parts);
    }

    private function blankToNull(?string $value): ?string
    {
        return $value === null || mb_trim($value) === '' ? null : $value;
    }

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

    private function withoutTheYear(string $format): string
    {
        return mb_trim((string) preg_replace('#[/.\-\s]*Y[/.\-\s]*#', ' ', $format));
    }

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
