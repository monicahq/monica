<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\DateFormatEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\NumberFormatEnum;
use App\Enums\PossessionFieldKindEnum;
use App\Models\FieldValue;
use App\Models\PossessionFieldValue;
use App\Models\User;
use App\Models\ValueListItem;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class FieldValueFormatter
{
    public function __construct(private readonly User $user) {}

    /** @return array<string, string> */
    public static function addressPartNames(): array
    {
        return [
            'street' => __('Street'),
            'extended' => __('Extended street line'),
            'po_box' => __('Post office box'),
            'city' => __('City'),
            'region' => __('Region'),
            'postal_code' => __('Postcode'),
            'country' => __('Country'),
        ];
    }

    /** @param  array<string, mixed>|null  $parts */
    public static function addressLine(?array $parts): ?string
    {
        $filled = array_filter(array_map(
            fn (string $part): string => mb_trim((string) ($parts[$part] ?? '')),
            FieldValue::ADDRESS_PARTS,
        ));

        return $filled === [] ? null : implode(', ', $filled);
    }

    public function format(FieldValue $value): ?string
    {
        return match ($value->field->type) {
            FieldTypeEnum::Text, FieldTypeEnum::Url => $this->blankToNull($value->value_string),
            FieldTypeEnum::Email, FieldTypeEnum::Phone, FieldTypeEnum::Address => $this->labelled($value),
            FieldTypeEnum::Textarea => $this->blankToNull($value->value_text),
            FieldTypeEnum::Number => $this->number($value),
            FieldTypeEnum::Boolean => $this->boolean($value),
            FieldTypeEnum::Date => $this->date($value),
            FieldTypeEnum::RecurringDate => $this->recurringDate($value),
            FieldTypeEnum::Select, FieldTypeEnum::Radio => $value->valueListItem?->label,
            FieldTypeEnum::Multiselect => $this->choices($value),
            FieldTypeEnum::File, FieldTypeEnum::Image,
            FieldTypeEnum::Avatar => $this->blankToNull($value->file?->original_name),
            FieldTypeEnum::Relation => $value->relatedContact->name ?? $value->relatedEntity?->name,
            default => null,
        };
    }

    public function formatPossessionValue(PossessionFieldValue $value): ?string
    {
        $kind = $value->field->kind;

        return match ($kind) {
            PossessionFieldKindEnum::Number => $this->possessionNumber($value->value),
            PossessionFieldKindEnum::Date => $this->possessionDate($value->value),
            PossessionFieldKindEnum::Boolean => PossessionFieldValueFormat::read($kind, $value->value) ? __('Yes') : __('No'),
            PossessionFieldKindEnum::Rating => __(':rating out of 5', ['rating' => PossessionFieldValueFormat::read($kind, $value->value)]),
            default => $this->blankToNull($value->value),
        };
    }

    private function possessionNumber(string $number): string
    {
        $decimals = 0;

        if (str_contains($number, '.')) {
            $decimals = mb_strlen(rtrim(explode('.', $number)[1], '0'));
        }

        return $this->numberFormat()->format((float) $number, $decimals);
    }

    private function possessionDate(string $date): string
    {
        $parsed = CarbonImmutable::createFromFormat('Y-m-d', $date);

        return $parsed instanceof CarbonImmutable ? $this->write($parsed, $this->dateFormat()) : $date;
    }

    private function labelled(FieldValue $value): ?string
    {
        $written = $value->field->type === FieldTypeEnum::Address
            ? self::addressLine(is_array($value->value_json) ? $value->value_json : null)
            : $this->blankToNull($value->value_string);
        $label = $this->blankToNull($value->label_wording) ?? $value->valueListItem?->label;

        if ($written === null || $label === null) {
            return $written;
        }

        return __(':value (:label)', ['value' => $written, 'label' => $label]);
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

        // SQLite hands a decimal column back as a float, MySQL and PostgreSQL as a string.
        $number = (string) $value->value_number;
        $decimals = 0;

        if (str_contains($number, '.')) {
            $decimals = mb_strlen(rtrim(explode('.', $number)[1], '0'));
        }

        return $this->numberFormat()->format((float) $number, $decimals);
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
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];
    }

    private function numberFormat(): NumberFormatEnum
    {
        return $this->user->number_format
            ?? NumberFormatEnum::tryFrom((string) AvailableLocales::details(app()->getLocale())['number_format'])
            ?? NumberFormatEnum::CommaAndDot;
    }
}
