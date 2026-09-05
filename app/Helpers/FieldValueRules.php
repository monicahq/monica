<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\FieldTypeEnum;
use App\Models\Field;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class FieldValueRules
{
    /**
     * @param  Collection<int, Field>  $fields
     * @return array<string, array<int, mixed>>
     */
    public static function for(Collection $fields): array
    {
        $rules = [];

        foreach ($fields as $field) {
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

    /** @return array<string, array<int, mixed>> */
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

            $rules["fields.{$field->id}.*.values.{$child->id}"] = self::forOne($child, required: false);

            if ($child->type === FieldTypeEnum::Multiselect) {
                $rules["fields.{$field->id}.*.values.{$child->id}.*"] = self::choiceRule($child);
            }
        }

        return $rules;
    }

    /** @return array<int, mixed> */
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

    /** @return array<int, mixed> */
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

    private static function choiceRule(Field $field): mixed
    {
        return Rule::exists('value_list_items', 'id')
            ->where('value_list_id', $field->value_list_id)
            ->where('is_active', true);
    }
}
