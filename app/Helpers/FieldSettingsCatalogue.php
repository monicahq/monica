<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\RepetitionOrderEnum;

/**
 * What each kind of field asks for beyond its name, and where the answer is kept.
 *
 * A number wants a unit and a range, a file wants the kinds it takes, a repeater
 * wants how many times it may repeat. The screen draws these and the action that
 * saves the screen validates and writes them, so they are described once here
 * rather than twice on either side.
 *
 * Where a setting is kept is part of its description. Some are columns of their
 * own on the field, the rest live together in its `options`, and a reader of
 * this catalogue never has to know which without being told.
 */
class FieldSettingsCatalogue
{
    /**
     * Settings held in a column of their own rather than in `options`. Anything
     * else named by a descriptor is an `options` key.
     *
     * @var list<string>
     */
    public const array COLUMNS = [
        'placeholder',
        'repeat_min',
        'repeat_max',
        'repetition_order',
        'relation_target',
    ];

    /**
     * Every setting the given kind of field takes, in the order they are shown.
     *
     * A field that points at something needs to know what, and what it may point
     * at is the account's own list, so the entity types are passed in keyed by
     * their number. Without them the only thing left to point at is somebody in
     * the vault, which is what an account that has defined none can offer.
     *
     * @param  array<int, string>  $entityTypes
     * @return list<array{key: string, label: string, control: string, width: string, placeholder: ?string, options: array<int, array{value: string, label: string}>}>
     */
    public static function of(FieldTypeEnum $type, array $entityTypes = []): array
    {
        return match ($type) {
            FieldTypeEnum::Text, FieldTypeEnum::Textarea => [
                self::input('placeholder', __('Example answer'), __('Dr Meyer'), 'wide'),
                self::number('max_length', __('Longest answer'), '120', 'narrow'),
            ],
            FieldTypeEnum::Number => [
                self::input('unit', __('Unit'), __('kg'), 'narrow'),
                self::number('min', __('Smallest'), '0', 'narrow'),
                self::number('max', __('Largest'), '200', 'narrow'),
                self::number('decimals', __('Decimals'), '1', 'narrow'),
            ],
            FieldTypeEnum::Boolean => [
                self::input('affirmative', __('Wording for yes'), __('Yes'), 'medium'),
                self::input('negative', __('Wording for no'), __('No'), 'medium'),
            ],
            FieldTypeEnum::Url => [
                self::input('placeholder', __('Example address'), 'https://', 'wide'),
            ],
            FieldTypeEnum::Email => [
                self::input('placeholder', __('Example address'), 'name@example.com', 'wide'),
            ],
            FieldTypeEnum::Phone => [
                self::input('placeholder', __('Example number'), '+44 7700 900461', 'wide'),
            ],
            FieldTypeEnum::Multiselect => [
                self::number('max_choices', __('Most that can be picked'), __('no limit'), 'medium'),
            ],
            FieldTypeEnum::Relation => [
                self::select('relation_target', __('What it points at'), self::pointsAt($entityTypes), 'medium'),
            ],
            FieldTypeEnum::File, FieldTypeEnum::Image, FieldTypeEnum::Avatar => [
                self::input('accepted_mime_types', __('Kinds of file taken'), __('image/png, application/pdf'), 'wide'),
            ],
            FieldTypeEnum::Repeater => [
                self::number('repeat_min', __('Fewest'), '0', 'narrow'),
                self::number('repeat_max', __('Most'), __('no limit'), 'narrow'),
                self::select('repetition_order', __('Where a new one goes'), [
                    RepetitionOrderEnum::NewestLast->value => __(RepetitionOrderEnum::NewestLast->label()),
                    RepetitionOrderEnum::NewestFirst->value => __(RepetitionOrderEnum::NewestFirst->label()),
                ], 'medium'),
            ],
            default => [],
        };
    }

    /**
     * Whether the given kind of field offers a list of choices somebody writes
     * out under it.
     */
    public static function offersChoices(FieldTypeEnum $type): bool
    {
        return $type->usesValueList();
    }

    /**
     * The value of one setting as the screen should show it, read back out of a
     * field that already holds it.
     *
     * @param  array<string, mixed>|null  $options
     */
    public static function read(string $key, ?array $options, mixed $column): string
    {
        $value = in_array($key, self::COLUMNS, true)
            ? $column
            : ($options[$key] ?? null);

        if (is_array($value)) {
            return implode(', ', $value);
        }

        return $value === null ? '' : (string) $value;
    }

    /**
     * Read what a relation points at out of the value a screen sent for it:
     * `contact`, or `entity:` followed by the number of an entity type. The
     * number is not looked up here, since only the caller knows whose it is.
     *
     * @return array{0: RelationTargetEnum, 1: int|null}|null
     */
    public static function target(string $value): ?array
    {
        if ($value === RelationTargetEnum::Contact->value) {
            return [RelationTargetEnum::Contact, null];
        }

        $prefix = RelationTargetEnum::Entity->value.':';

        if (! str_starts_with($value, $prefix) || ! ctype_digit(substr($value, strlen($prefix)))) {
            return null;
        }

        return [RelationTargetEnum::Entity, (int) substr($value, strlen($prefix))];
    }

    /**
     * Split a line of several things written one after the other, dropping the
     * blanks a stray comma leaves behind.
     *
     * @return list<string>
     */
    public static function commaSeparated(string $value): array
    {
        return array_values(array_filter(array_map(trim(...), explode(',', $value)), fn (string $part): bool => $part !== ''));
    }

    /**
     * @param  array<int, string>  $entityTypes
     * @return array<string, string>
     */
    private static function pointsAt(array $entityTypes): array
    {
        $targets = [RelationTargetEnum::Contact->value => __('Somebody in the vault')];

        foreach ($entityTypes as $id => $name) {
            $targets[RelationTargetEnum::Entity->value.':'.$id] = $name;
        }

        return $targets;
    }

    /**
     * @return array{key: string, label: string, control: string, width: string, placeholder: ?string, options: array<int, array{value: string, label: string}>}
     */
    private static function input(string $key, string $label, string $placeholder, string $width): array
    {
        return ['key' => $key, 'label' => $label, 'control' => 'input', 'width' => $width, 'placeholder' => $placeholder, 'options' => []];
    }

    /**
     * @return array{key: string, label: string, control: string, width: string, placeholder: ?string, options: array<int, array{value: string, label: string}>}
     */
    private static function number(string $key, string $label, string $placeholder, string $width): array
    {
        return ['key' => $key, 'label' => $label, 'control' => 'number', 'width' => $width, 'placeholder' => $placeholder, 'options' => []];
    }

    /**
     * @param  array<string, string>  $options
     * @return array{key: string, label: string, control: string, width: string, placeholder: ?string, options: array<int, array{value: string, label: string}>}
     */
    private static function select(string $key, string $label, array $options, string $width): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'control' => 'select',
            'width' => $width,
            'placeholder' => null,
            'options' => array_map(
                fn (string $value, string $optionLabel): array => ['value' => $value, 'label' => $optionLabel],
                array_keys($options),
                array_values($options),
            ),
        ];
    }
}
