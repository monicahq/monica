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
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

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

    /**
     * The lines of an address in the layout of its country, with the stored country text last.
     * An address whose country has no known code keeps the order its parts are stored in.
     *
     * @param  array<string, mixed>|null  $parts
     * @return list<string>
     */
    public static function addressLines(?array $parts, int $accountId): array
    {
        $filled = [];

        foreach (FieldValue::ADDRESS_PARTS as $part) {
            $text = mb_trim((string) ($parts[$part] ?? ''));

            if ($text !== '') {
                $filled[$part] = $text;
            }
        }

        $recordedCode = is_string($parts['country_code'] ?? null) ? mb_strtoupper($parts['country_code']) : null;
        $recordedName = $recordedCode === null ? null : CountryCodes::nameOf($recordedCode, app()->getLocale());

        if ($recordedName !== null) {
            $filled['country'] = $recordedName;
        }

        $code = match (true) {
            $recordedName !== null => $recordedCode,
            isset($filled['country']) => app(CountryCodes::class)->codeFor($accountId, $filled['country']),
            default => null,
        };

        if ($code === null) {
            return array_values($filled);
        }

        $tokens = [
            '%addressLine1' => 'street',
            '%addressLine2' => 'extended',
            '%addressLine3' => 'po_box',
            '%locality' => 'city',
            '%administrativeArea' => 'region',
            '%postalCode' => 'postal_code',
        ];
        $format = new AddressFormatRepository()->get($code)->getFormat();
        $values = array_map(fn (string $part): string => $filled[$part] ?? '', $tokens);
        $lines = [];

        foreach (explode("\n", $format) as $formatLine) {
            $line = self::fillFormatLine($formatLine, $values);

            if ($line !== '') {
                $lines[] = $line;
            }
        }

        foreach ($tokens as $token => $part) {
            if (isset($filled[$part]) && ! str_contains($format, $token)) {
                $lines[] = $filled[$part];
            }
        }

        $lines[] = $filled['country'];

        return $lines;
    }

    /** @param  array<string, mixed>|null  $parts */
    public static function addressLine(?array $parts, int $accountId): ?string
    {
        $lines = self::addressLines($parts, $accountId);

        return $lines === [] ? null : implode(', ', $lines);
    }

    /**
     * A number written with its international prefix, grouped the way its country groups it.
     * Anything else, a number holding words included, reads as it was typed.
     */
    public static function phone(string $text): string
    {
        if (preg_match('/^\+[\d\s().\/-]+$/', mb_trim($text)) !== 1) {
            return $text;
        }

        $numbers = PhoneNumberUtil::getInstance();

        try {
            $number = $numbers->parse($text, null);
        } catch (NumberParseException) {
            return $text;
        }

        return $numbers->isValidNumber($number) ? $numbers->format($number, PhoneNumberFormat::INTERNATIONAL) : $text;
    }

    public function format(FieldValue $value): ?string
    {
        return match ($value->field->type) {
            FieldTypeEnum::Text, FieldTypeEnum::Url => $this->blankToNull($value->value_string),
            FieldTypeEnum::Email, FieldTypeEnum::Phone, FieldTypeEnum::Address => $this->labelled($value),
            FieldTypeEnum::Textarea => $this->blankToNull($value->value_text),
            FieldTypeEnum::Number => $this->number($value),
            FieldTypeEnum::Duration => $value->value_number === null ? null : Duration::wording((int) $value->value_number),
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

    /**
     * The value as the search engine reads it. A phone number keeps the digits it was typed with,
     * so that grouping them does not split what somebody searches for, and keeps its label.
     */
    public function formatForSearch(FieldValue $value): ?string
    {
        if ($value->field->type !== FieldTypeEnum::Phone) {
            return $this->format($value);
        }

        return $this->labelled($value, groupsThePhone: false);
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

    private function labelled(FieldValue $value, bool $groupsThePhone = true): ?string
    {
        $written = match ($value->field->type) {
            FieldTypeEnum::Address => self::addressLine(is_array($value->value_json) ? $value->value_json : null, $value->account_id),
            FieldTypeEnum::Phone => $groupsThePhone && $this->blankToNull($value->value_string) !== null
                ? self::phone((string) $value->value_string)
                : $this->blankToNull($value->value_string),
            default => $this->blankToNull($value->value_string),
        };
        $label = $this->blankToNull($value->label_wording) ?? $value->valueListItem?->label;

        if ($written === null || $label === null) {
            return $written;
        }

        return __(':value (:label)', ['value' => $written, 'label' => $label]);
    }

    /**
     * Fills one line of a postal format, dropping the separator an empty part leaves behind, the
     * way the package's own formatter does.
     *
     * @param  array<string, string>  $values
     */
    private static function fillFormatLine(string $formatLine, array $values): string
    {
        $line = '';
        $separator = '';
        $skipped = false;

        foreach (preg_split('/(%[a-zA-Z0-9]+)/', $formatLine, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [] as $piece) {
            if (! str_starts_with($piece, '%')) {
                if (! $skipped) {
                    $separator = $piece;
                }

                continue;
            }

            $value = $values[$piece] ?? '';

            if ($value === '') {
                $skipped = true;

                continue;
            }

            if ($line !== '' || ! $skipped) {
                $line .= $separator;
            }

            $line .= $value;
            $separator = '';
            $skipped = false;
        }

        return mb_trim((string) preg_replace('/\s\s+/', ' ', $line), ' -,');
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
