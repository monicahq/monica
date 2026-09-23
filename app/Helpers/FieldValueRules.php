<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\FieldTypeEnum;
use App\Enums\ValueListEnum;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Closure;
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

            if ($field->type === FieldTypeEnum::Address) {
                $rules = array_merge($rules, self::forAddressParts($field, "fields.{$field->id}"));
            }

            if (self::offersALabel($field)) {
                $rules["labels.{$field->id}"] = ['nullable', self::choiceRule($field)];
                $rules["label_wordings.{$field->id}"] = ['nullable', 'string', 'max:255'];
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
            "fields.{$field->id}.*.labels" => ['nullable', 'array'],
            "fields.{$field->id}.*.label_wordings" => ['nullable', 'array'],
        ];

        foreach ($field->children as $child) {
            if (! $child->type->hasAnInput() || $child->type->isRepeater()) {
                continue;
            }

            $rules["fields.{$field->id}.*.values.{$child->id}"] = self::forOne($child, required: false);

            if ($child->type === FieldTypeEnum::Multiselect) {
                $rules["fields.{$field->id}.*.values.{$child->id}.*"] = self::choiceRule($child);
            }

            if ($child->type === FieldTypeEnum::Address) {
                $rules = array_merge($rules, self::forAddressParts($child, "fields.{$field->id}.*.values.{$child->id}"));
            }

            if (self::offersALabel($child)) {
                $rules["fields.{$field->id}.*.labels.{$child->id}"] = ['nullable', self::choiceRule($child)];
                $rules["fields.{$field->id}.*.label_wordings.{$child->id}"] = ['nullable', 'string', 'max:255'];
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
            FieldTypeEnum::Multiselect, FieldTypeEnum::Address => ['array'],
            FieldTypeEnum::File, FieldTypeEnum::Image, FieldTypeEnum::Avatar => self::fileRules($field),
            default => [],
        });
    }

    /** @return array<string, array<int, mixed>> */
    private static function forAddressParts(Field $field, string $prefix): array
    {
        $rules = [];

        foreach (FieldValue::ADDRESS_PARTS as $part) {
            $rules["{$prefix}.{$part}"] = ['nullable', 'string', 'max:255'];
        }

        $countries = ValueList::query()
            ->where('account_id', $field->account_id)
            ->where('key', ValueListEnum::Countries->value)
            ->first();

        $rules["{$prefix}.country"] = [
            'nullable',
            function (string $attribute, mixed $value, Closure $fail) use ($countries): void {
                if ($value === 'keep') {
                    return;
                }

                $offered = $countries instanceof ValueList
                    && filter_var($value, FILTER_VALIDATE_INT) !== false
                    && ValueListItem::query()
                        ->whereKey($value)
                        ->where('value_list_id', $countries->id)
                        ->where('is_active', true)
                        ->exists();

                if (! $offered) {
                    $fail('validation.exists')->translate();
                }
            },
        ];

        return $rules;
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

    private static function offersALabel(Field $field): bool
    {
        return $field->type->usesALabelList() && $field->value_list_id !== null;
    }

    private static function choiceRule(Field $field): mixed
    {
        return Rule::exists('value_list_items', 'id')
            ->where('value_list_id', $field->value_list_id)
            ->where('is_active', true);
    }
}
