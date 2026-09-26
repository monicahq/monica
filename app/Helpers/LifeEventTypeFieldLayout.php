<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\FieldTypeEnum;
use Illuminate\Validation\Rule;

class LifeEventTypeFieldLayout
{
    private const array COUNTED_SETTINGS = [
        'max_length',
        'min',
        'max',
        'decimals',
        'max_choices',
        'repeat_min',
        'repeat_max',
    ];

    /** @return array<string, array<int, mixed>> */
    public static function rules(): array
    {
        $rules = [
            'groups' => ['nullable', 'array'],
            'groups.*.placement_id' => ['nullable', 'integer'],
            'groups.*.name' => ['required', 'string', 'max:255'],
            'groups.*.fields' => ['nullable', 'array'],
            'fields' => ['nullable', 'array'],
        ];

        foreach (['groups.*.fields.*', 'fields.*'] as $path) {
            $rules["{$path}.key"] = ['nullable', 'string', 'max:64'];
            $rules["{$path}.label"] = ['required', 'string', 'max:255'];
            $rules["{$path}.type"] = ['required', Rule::enum(FieldTypeEnum::class)];
            $rules["{$path}.is_required"] = ['nullable', 'boolean'];
            $rules["{$path}.help_text"] = ['nullable', 'string', 'max:255'];
            $rules["{$path}.settings"] = ['nullable', 'array'];
            $rules["{$path}.settings.*"] = ['nullable', 'string', 'max:255'];
            $rules["{$path}.choices"] = ['nullable', 'array'];
            $rules["{$path}.choices.*"] = ['nullable', 'string', 'max:255'];

            foreach (self::COUNTED_SETTINGS as $setting) {
                $rules["{$path}.settings.{$setting}"] = ['nullable', 'integer', 'min:0'];
            }
        }

        return $rules;
    }

    /**
     * The groups as the action wants them, each named by where it sits in what was sent, since a
     * caller describes a group by holding its fields rather than by inventing a reference for it.
     *
     * @param  array<mixed>  $groups
     * @return list<array{reference: string, placement_id: ?int, name: string}>
     */
    public static function groups(array $groups): array
    {
        $wanted = [];

        foreach (array_values($groups) as $index => $group) {
            $wanted[] = [
                'reference' => 'g'.$index,
                'placement_id' => ($group['placement_id'] ?? null) === null ? null : (int) $group['placement_id'],
                'name' => (string) $group['name'],
            ];
        }

        return $wanted;
    }

    /**
     * Every field of every group, then the ones nobody grouped, which the action files under the
     * empty reference.
     *
     * @param  array<mixed>  $groups
     * @param  array<mixed>  $ungrouped
     * @return list<array{key: ?string, group: string, label: string, type: string, is_required: bool, help_text: ?string, settings: array<string, string>, choices: list<string>}>
     */
    public static function fields(array $groups, array $ungrouped): array
    {
        $fields = [];

        foreach (array_values($groups) as $index => $group) {
            foreach ((array) ($group['fields'] ?? []) as $field) {
                $fields[] = self::field($field, 'g'.$index);
            }
        }

        foreach ($ungrouped as $field) {
            $fields[] = self::field($field, '');
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array{key: ?string, group: string, label: string, type: string, is_required: bool, help_text: ?string, settings: array<string, string>, choices: list<string>}
     */
    private static function field(array $field, string $group): array
    {
        return [
            'key' => ($field['key'] ?? null) === null ? null : (string) $field['key'],
            'group' => $group,
            'label' => (string) $field['label'],
            'type' => (string) $field['type'],
            'is_required' => (bool) ($field['is_required'] ?? false),
            'help_text' => ($field['help_text'] ?? null) === null ? null : (string) $field['help_text'],
            'settings' => array_map(strval(...), array_filter((array) ($field['settings'] ?? []), fn (mixed $value): bool => $value !== null)),
            'choices' => array_values(array_filter(
                array_map(fn (mixed $choice): string => trim((string) $choice), (array) ($field['choices'] ?? [])),
                fn (string $choice): bool => $choice !== '',
            )),
        ];
    }
}
