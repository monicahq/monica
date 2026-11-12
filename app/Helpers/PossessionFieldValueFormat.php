<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\PossessionFieldKindEnum;
use App\Models\PossessionField;
use DateTimeImmutable;

/**
 * How a value of a possession field is checked, stored and read back. It is stored as text,
 * normalised by the kind of the field: text and choices as written, a number as a decimal string, a
 * date as Y-m-d, yes or no as 1 or 0, a rating as 1 to 5.
 */
class PossessionFieldValueFormat
{
    public const int MAX_TEXT_LENGTH = 2000;

    public static function isEmpty(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim(TextSanitizer::plainText($value)) === '');
    }

    /**
     * Why the value does not fit the field, or null when it does.
     */
    public static function refusal(PossessionField $field, mixed $value): ?string
    {
        if (self::normalise($field, $value) !== null) {
            return null;
        }

        return match ($field->kind) {
            PossessionFieldKindEnum::Text => __('Write at most :max characters in :name.', ['max' => self::MAX_TEXT_LENGTH, 'name' => $field->name]),
            PossessionFieldKindEnum::Number => __(':name takes a number.', ['name' => $field->name]),
            PossessionFieldKindEnum::Date => __(':name takes a date written as year, month and day, like 2005-03-24.', ['name' => $field->name]),
            PossessionFieldKindEnum::Boolean => __(':name takes yes or no.', ['name' => $field->name]),
            PossessionFieldKindEnum::Select => __(':name takes one of its own choices.', ['name' => $field->name]),
            PossessionFieldKindEnum::Rating => __(':name takes a rating from one to five.', ['name' => $field->name]),
        };
    }

    /**
     * The text to store for a value that is not empty, or null when it does not fit the field.
     */
    public static function normalise(PossessionField $field, mixed $value): ?string
    {
        return match ($field->kind) {
            PossessionFieldKindEnum::Text => self::text($value),
            PossessionFieldKindEnum::Number => self::number($value),
            PossessionFieldKindEnum::Date => self::date($value),
            PossessionFieldKindEnum::Boolean => self::boolean($value),
            PossessionFieldKindEnum::Select => self::choice($field, $value),
            PossessionFieldKindEnum::Rating => self::rating($value),
        };
    }

    /**
     * The stored text, read back as the kind of the field says.
     */
    public static function read(PossessionFieldKindEnum $kind, string $value): string|int|float|bool
    {
        return match ($kind) {
            PossessionFieldKindEnum::Number => str_contains($value, '.') || str_contains($value, 'E') ? (float) $value : (int) $value,
            PossessionFieldKindEnum::Boolean => $value === '1',
            PossessionFieldKindEnum::Rating => (int) $value,
            default => $value,
        };
    }

    private static function text(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return null;
        }

        $text = TextSanitizer::plainText((string) $value);

        return mb_strlen($text) <= self::MAX_TEXT_LENGTH ? $text : null;
    }

    private static function number(mixed $value): ?string
    {
        if (is_bool($value) || ! is_numeric($value)) {
            return null;
        }

        $number = $value + 0;

        return is_finite($number) ? (string) $number : null;
    }

    private static function date(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }

    private static function boolean(mixed $value): ?string
    {
        $answer = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($answer === null) {
            return null;
        }

        return $answer ? '1' : '0';
    }

    private static function choice(PossessionField $field, mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $choice = TextSanitizer::plainText($value);

        return in_array($choice, $field->options ?? [], true) ? $choice : null;
    }

    private static function rating(mixed $value): ?string
    {
        $rating = filter_var($value, FILTER_VALIDATE_INT);

        if (is_bool($value) || $rating === false || $rating < 1 || $rating > PossessionFieldKindEnum::MAX_RATING) {
            return null;
        }

        return (string) $rating;
    }
}
