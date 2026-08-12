<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Actions\SetFieldValue;
use App\Enums\FieldTypeEnum;
use App\Models\Field;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * The validation rules a set of fields asks for, built from the fields
 * themselves.
 *
 * A template is invented by the user, so the rules a form enforces cannot be
 * written down anywhere in advance; they are read off the rows. What is read is
 * only what the row already states: the type, whether an answer is required,
 * and which list a choice has to come from. The `validation` column is not read
 * at all, because whether it is a free form structure or a fixed vocabulary is
 * still an open question, and enforcing half of an undecided thing is worse
 * than enforcing none of it.
 *
 * Uniqueness is deliberately not here. It is a question about the other records
 * in the vault rather than about the input, and it is answered by
 * {@see SetFieldValue} where the vault is in hand.
 *
 * It is a class of its own rather than a method on the form request, so the API
 * enforces the same rules as the interface by using the same code.
 */
class FieldValueRules
{
    /**
     * The rules for every field given, keyed the way the form names its inputs.
     *
     * @param  Collection<int, Field>  $fields
     * @return array<string, array<int, mixed>>
     */
    public static function for(Collection $fields): array
    {
        $rules = [];

        foreach ($fields as $field) {
            /* A type with no control on the page is not something the form can
               have got wrong, so it is not something the form validates. */
            if (! $field->type->hasAnInput()) {
                continue;
            }

            $rules["fields.{$field->id}"] = self::forOne($field);

            if ($field->type === FieldTypeEnum::Multiselect) {
                $rules["fields.{$field->id}.*"] = self::choiceRule($field);
            }
        }

        return $rules;
    }

    /**
     * @return array<int, mixed>
     */
    private static function forOne(Field $field): array
    {
        $rules = [$field->is_required ? 'required' : 'nullable'];

        return array_merge($rules, match ($field->type) {
            FieldTypeEnum::Text, FieldTypeEnum::Phone => ['string', 'max:1024'],
            FieldTypeEnum::Textarea => ['string', 'max:65535'],
            FieldTypeEnum::Url => ['string', 'url', 'max:1024'],
            FieldTypeEnum::Email => ['string', 'email', 'max:1024'],
            FieldTypeEnum::Number => ['numeric'],
            FieldTypeEnum::Boolean => ['boolean'],
            FieldTypeEnum::Date, FieldTypeEnum::RecurringDate => ['date'],
            FieldTypeEnum::Select, FieldTypeEnum::Radio => [self::choiceRule($field)],
            FieldTypeEnum::Multiselect => ['array'],
            default => [],
        });
    }

    /**
     * A choice has to be on the list the field offers, and has to be one of the
     * items still being offered: an item somebody stopped offering is kept on
     * every record that already holds it and is not handed out again.
     */
    private static function choiceRule(Field $field): mixed
    {
        return Rule::exists('value_list_items', 'id')
            ->where('value_list_id', $field->value_list_id)
            ->where('is_active', true);
    }
}
