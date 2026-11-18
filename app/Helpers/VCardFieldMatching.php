<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use Illuminate\Support\Collection;
use Sabre\VObject\Property;

/**
 * Which value of a contact each line of a card stands for. A line belongs to the value it is equal
 * to, so a card that comes back unchanged lands on the fields it was written from. What is left on
 * either side is paired in order, which is how a value changed on one side replaces the old one on
 * the other. A line nothing claims is left alone, except on a card creating a contact, where it
 * fills the first field with room. A field the key cannot reach claims its own line and nothing
 * else, so that line can be withheld.
 */
class VCardFieldMatching
{
    private readonly OwnedFields $owned;

    public function __construct(
        Contact $contact,
        private readonly bool $reachesSensitiveFields = false,
        bool $preloaded = false,
    ) {
        $this->owned = new OwnedFields($contact, preloaded: $preloaded);
    }

    /** @return array<int, string> */
    public function properties(): array
    {
        return $this->owned->properties();
    }

    /**
     * A pair without a line is a value the card does not carry, and a pair without a value is a
     * line filling an empty field. Withheld lines belong to a field the key cannot reach.
     *
     * @param  array<int, Property>  $lines
     * @param  Collection<int, FieldValue>  $values
     * @return array{pairs: list<array{field: Field, value: ?FieldValue, line: ?Property}>, withheld: list<Property>}
     */
    public function match(string $property, array $lines, Collection $values, bool $fillsEmptyFields = false): array
    {
        $lines = array_values($lines);
        $free = array_keys($lines);
        $pairs = [];
        $withheld = [];
        $unpaired = [];

        foreach ($this->held($property, $values) as $entry) {
            $found = null;

            foreach ($free as $position => $index) {
                if (self::comparable($property, self::formOfLine($lines[$index])) === $entry['comparable']) {
                    $found = $position;

                    break;
                }
            }

            if ($found === null) {
                $unpaired[] = $entry;

                continue;
            }

            $line = $lines[$free[$found]];
            unset($free[$found]);

            if ($this->withholds($entry['field'])) {
                $withheld[] = $line;

                continue;
            }

            $pairs[] = ['field' => $entry['field'], 'value' => $entry['value'], 'line' => $line];
        }

        foreach ($unpaired as $entry) {
            if ($this->withholds($entry['field'])) {
                continue;
            }

            $index = array_shift($free);
            $line = $index === null
                ? null
                : $lines[$index];

            $pairs[] = ['field' => $entry['field'], 'value' => $entry['value'], 'line' => $line];
        }

        if (! $fillsEmptyFields) {
            return ['pairs' => $pairs, 'withheld' => $withheld];
        }

        foreach ($this->owned->for($property) as $field) {
            $growable = $field->parent_field_id !== null;

            if ($this->withholds($field)) {
                continue;
            }

            if (! $growable && $values->contains('field_id', $field->id)) {
                continue;
            }

            while ($free !== []) {
                $pairs[] = ['field' => $field, 'value' => null, 'line' => $lines[array_shift($free)]];

                if (! $growable) {
                    break;
                }
            }
        }

        return ['pairs' => $pairs, 'withheld' => $withheld];
    }

    /**
     * What a value looks like on a card, or null when the card cannot carry it.
     *
     * @return string|array<int, string>|null
     */
    public static function cardFormOf(Field $field, FieldValue $value): string|array|null
    {
        return match (OwnedFields::propertyFor($field)) {
            'ADR' => self::address($value),
            'BDAY', 'X-ABDATE' => self::recurringDate($value),
            default => self::text($value->value_string),
        };
    }

    private static function text(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_trim($value) === '' ? null : $value;
    }

    /**
     * The values of one property a card can carry, in the order of the structure.
     *
     * @param  Collection<int, FieldValue>  $values
     * @return list<array{field: Field, value: FieldValue, comparable: string}>
     */
    private function held(string $property, Collection $values): array
    {
        $held = [];

        foreach ($this->owned->for($property) as $field) {
            foreach ($values->where('field_id', $field->id) as $value) {
                $form = self::cardFormOf($field, $value);

                if ($form !== null) {
                    $held[] = ['field' => $field, 'value' => $value, 'comparable' => self::comparable($property, $form)];
                }
            }
        }

        return $held;
    }

    private function withholds(Field $field): bool
    {
        return $field->is_sensitive && ! $this->reachesSensitiveFields;
    }

    /** @return string|array<int, string> */
    private static function formOfLine(Property $line): string|array
    {
        if ($line->name === 'ADR') {
            return array_map(strval(...), $line->getParts());
        }

        return (string) $line->getValue();
    }

    /**
     * The form two writings of one value share: a phone number without its spacing, an address
     * without its trailing empty parts, a date without its separators.
     *
     * @param  string|array<int, string>  $form
     */
    private static function comparable(string $property, string|array $form): string
    {
        if (is_array($form)) {
            return rtrim(implode(';', array_map(mb_trim(...), $form)), ';');
        }

        $form = mb_trim($form);
        $yearless = str_starts_with($form, '--') ? '--' : '';

        return match ($property) {
            'TEL' => (string) preg_replace('/[^0-9+]/', '', (string) preg_replace('/^tel:/i', '', $form)),
            'EMAIL' => mb_strtolower((string) preg_replace('/^mailto:/i', '', $form)),
            'BDAY', 'X-ABDATE' => $yearless.preg_replace('/\D/', '', explode('T', $form)[0]),
            default => $form,
        };
    }

    /** @return array<int, string>|null */
    private static function address(FieldValue $value): ?array
    {
        $parts = $value->value_json;

        if (! is_array($parts)) {
            return null;
        }

        $address = array_map(
            fn (string $part): string => (string) ($parts[$part] ?? ''),
            FieldValue::ADDRESS_PARTS,
        );

        return implode('', $address) === '' ? null : $address;
    }

    private static function recurringDate(FieldValue $value): ?string
    {
        $date = $value->value_date;

        if ($date === null || $value->month_is_unknown) {
            return null;
        }

        if ($value->year_is_unknown) {
            return $date->format('--md');
        }

        return $date->format('Y-m-d');
    }
}
