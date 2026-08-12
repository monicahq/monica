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
 * which list a choice has to come from, how many repetitions a repeater allows
 * and which kinds of file it accepts. The `validation` column is not read at
 * all, because whether it is a free form structure or a fixed vocabulary is
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

            if ($field->type->isRepeater()) {
                $rules = array_merge($rules, self::forRepetitionsOf($field));

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
     * What a repeater and its repetitions may arrive as.
     *
     * The repetitions come in as a list rather than keyed by anything, because
     * a repetition somebody has just added has no number yet: it carries the id
     * of the row it already is, or nothing at all, and the save decides which of
     * the two it is looking at. `repeat_max` is enforced here as well as where
     * the row is written, so somebody who adds a seventh document is told under
     * the control rather than by a sentence at the top of the block.
     *
     * @return array<string, array<int, mixed>>
     */
    private static function forRepetitionsOf(Field $field): array
    {
        $rules = [
            "fields.{$field->id}" => array_filter([
                $field->is_required ? 'required' : 'nullable',
                'array',
                $field->repeat_max === null ? null : "max:{$field->repeat_max}",
            ]),
            "fields.{$field->id}.*.id" => ['nullable', 'integer'],
            "fields.{$field->id}.*.values" => ['nullable', 'array'],
        ];

        foreach ($field->children as $child) {
            if (! $child->type->hasAnInput() || $child->type->isRepeater()) {
                continue;
            }

            /* A repetition is required or not as a whole, and its own fields are
               asked for once it exists. Marking a child as required here would
               refuse a repetition somebody is halfway through rather than the
               one they left blank, which is the one thing a repeater must not
               do: an empty repetition is how somebody says they are done. */
            $rules["fields.{$field->id}.*.values.{$child->id}"] = self::forOne($child, required: false);

            if ($child->type === FieldTypeEnum::Multiselect) {
                $rules["fields.{$field->id}.*.values.{$child->id}.*"] = self::choiceRule($child);
            }
        }

        return $rules;
    }

    /**
     * @return array<int, mixed>
     */
    private static function forOne(Field $field, ?bool $required = null): array
    {
        $required ??= $field->is_required;

        $rules = [$required ? 'required' : 'nullable'];

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
            FieldTypeEnum::File, FieldTypeEnum::Image, FieldTypeEnum::Avatar => self::fileRules($field),
            default => [],
        });
    }

    /**
     * What an upload has to be.
     *
     * The kinds are read off the field, and read as mime types rather than as
     * extensions, so renaming a program to `holiday.jpg` does not get it past
     * the question of what it actually is. A field that names none takes
     * anything the account has room for.
     *
     * The size ceiling is not the account's; that is a question about the
     * account and is answered where the file is written. This is the smaller
     * question of whether the request can be handled at all, and it is asked
     * here so the answer arrives under the control rather than as a truncated
     * upload nobody explains.
     *
     * @return array<int, mixed>
     */
    private static function fileRules(Field $field): array
    {
        $rules = ['file', 'max:'.(int) config('monica.max_file_size_in_kilobytes')];

        if ($field->type === FieldTypeEnum::Image || $field->type === FieldTypeEnum::Avatar) {
            $rules[] = 'image';
        }

        $accepted = $field->acceptedMimeTypes();

        if ($accepted !== []) {
            $rules[] = 'mimetypes:'.implode(',', $accepted);
        }

        return $rules;
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
