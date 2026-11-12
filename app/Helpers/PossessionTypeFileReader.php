<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\PossessionFieldKindEnum;
use App\Enums\VaultColourEnum;
use App\Exceptions\PossessionTypeFileIsNotReadable;
use App\Models\Section;
use Illuminate\Support\Number;
use JsonException;

/**
 * Read a possession type file and say everything that is wrong with it at once, so whoever pasted it
 * fixes it in one pass. What it returns is clean: names trimmed, kinds known, choices only on select
 * fields, and a vault colour, Slate when the file names none.
 */
class PossessionTypeFileReader
{
    public const int MAX_BYTES = 102400;

    public const int MAX_GROUPS = 50;

    public const int MAX_FIELDS = 300;

    /** @var list<string> */
    private array $problems = [];

    private int $fieldCount = 0;

    /**
     * @return array{name: string, colour: string, icon: string|null, groups: list<array{name: string, fields: list<array{name: string, kind: PossessionFieldKindEnum, options: list<string>|null}>}>, fields: list<array{name: string, kind: PossessionFieldKindEnum, options: list<string>|null}>}
     *
     * @throws PossessionTypeFileIsNotReadable
     */
    public static function read(string $raw): array
    {
        return new self()->readFile($raw);
    }

    /**
     * @return array{name: string, colour: string, icon: string|null, groups: list<array{name: string, fields: list<array{name: string, kind: PossessionFieldKindEnum, options: list<string>|null}>}>, fields: list<array{name: string, kind: PossessionFieldKindEnum, options: list<string>|null}>}
     */
    private function readFile(string $raw): array
    {
        $document = $this->decode($raw);

        if (($document['format'] ?? null) !== PossessionTypeFile::FORMAT) {
            $this->problems[] = __('This file does not hold a possession type. Export one from a possession type to get a file that does.');
        }

        $version = $document['format_version'] ?? null;

        if (! is_int($version)) {
            $this->problems[] = __('The file does not say which format version wrote it.');
        }

        if (is_int($version) && $version > PossessionTypeFile::FORMAT_VERSION) {
            $this->problems[] = __('This file was written in format :version, which is newer than this Monica reads.', ['version' => $version]);
        }

        $type = $document['possession_type'] ?? null;

        if (! is_array($type) || array_is_list($type)) {
            $this->problems[] = __('The file holds no possession type.');

            throw new PossessionTypeFileIsNotReadable($this->problems);
        }

        $read = [
            'name' => $this->name($type['name'] ?? null, __('The type')),
            'colour' => $this->colour($type['colour'] ?? null),
            'icon' => $this->icon($type['icon'] ?? null),
            'groups' => $this->groups($type['groups'] ?? []),
            'fields' => $this->fields($type['fields'] ?? [], __('The standalone fields')),
        ];

        if ($this->fieldCount > self::MAX_FIELDS) {
            $this->problems[] = trans_choice('[0,*]A type holds at most :count fields.', self::MAX_FIELDS);
        }

        if ($this->problems !== []) {
            throw new PossessionTypeFileIsNotReadable($this->problems);
        }

        return $read;
    }

    /** @return array<string, mixed> */
    private function decode(string $raw): array
    {
        if (mb_trim($raw) === '') {
            throw new PossessionTypeFileIsNotReadable([__('There is nothing here to read. Paste the file, outer braces included.')]);
        }

        if (strlen($raw) > self::MAX_BYTES) {
            throw new PossessionTypeFileIsNotReadable([__('This file is larger than :size, so it was not read.', ['size' => Number::fileSize(self::MAX_BYTES)])]);
        }

        try {
            $document = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PossessionTypeFileIsNotReadable([__('This is not valid JSON: :message', ['message' => $exception->getMessage()])]);
        }

        if (! is_array($document) || array_is_list($document)) {
            throw new PossessionTypeFileIsNotReadable([__('The file must be a JSON object, between braces.')]);
        }

        return $document;
    }

    private function name(mixed $name, string $label): string
    {
        $cleaned = is_string($name) ? TextSanitizer::plainText($name) : '';

        if ($cleaned === '') {
            $this->problems[] = __(':label needs a name.', ['label' => $label]);

            return $cleaned;
        }

        if (mb_strlen($cleaned) > 255) {
            $this->problems[] = __('The name of :label is longer than 255 characters.', ['label' => $label]);
        }

        return $cleaned;
    }

    private function icon(mixed $icon): ?string
    {
        if ($icon === null) {
            return null;
        }

        if (! is_string($icon) || ! in_array($icon, Section::ICONS, true)) {
            $this->problems[] = __('The icon is not one of the glyphs of the catalogue.');

            return null;
        }

        return $icon;
    }

    private function colour(mixed $colour): string
    {
        if ($colour === null) {
            return VaultColourEnum::Slate->value;
        }

        if (! is_string($colour) || VaultColourEnum::tryFrom(mb_strtolower($colour)) === null) {
            $this->problems[] = __('The colour is not one of the vault colours.');

            return VaultColourEnum::Slate->value;
        }

        return mb_strtolower($colour);
    }

    /** @return list<array{name: string, fields: list<array{name: string, kind: PossessionFieldKindEnum, options: list<string>|null}>}> */
    private function groups(mixed $groups): array
    {
        if (! is_array($groups) || ! array_is_list($groups)) {
            $this->problems[] = __('The groups must be a list.');

            return [];
        }

        if (count($groups) > self::MAX_GROUPS) {
            $this->problems[] = trans_choice('[0,*]A type holds at most :count groups.', self::MAX_GROUPS);

            return [];
        }

        $read = [];

        foreach ($groups as $index => $group) {
            $label = __('Group :position', ['position' => $index + 1]);

            if (! is_array($group) || array_is_list($group)) {
                $this->problems[] = __(':label must be an object.', ['label' => $label]);

                continue;
            }

            $read[] = [
                'name' => $this->name($group['name'] ?? null, $label),
                'fields' => $this->fields($group['fields'] ?? [], $label),
            ];
        }

        return $read;
    }

    /** @return list<array{name: string, kind: PossessionFieldKindEnum, options: list<string>|null}> */
    private function fields(mixed $fields, string $owner): array
    {
        if (! is_array($fields) || ! array_is_list($fields)) {
            $this->problems[] = __(':label: the fields must be a list.', ['label' => $owner]);

            return [];
        }

        $read = [];

        foreach ($fields as $index => $field) {
            $this->fieldCount++;
            $label = __(':owner, field :position', ['owner' => $owner, 'position' => $index + 1]);

            if (! is_array($field) || array_is_list($field)) {
                $this->problems[] = __(':label must be an object.', ['label' => $label]);

                continue;
            }

            $name = $this->name($field['name'] ?? null, $label);
            $kind = is_string($field['kind'] ?? null) ? PossessionFieldKindEnum::tryFrom($field['kind']) : null;

            if ($kind === null) {
                $this->problems[] = __(':label has an unknown kind.', ['label' => $label]);

                continue;
            }

            $rawOptions = is_array($field['options'] ?? null) ? $field['options'] : [];

            if (array_filter($rawOptions, fn (mixed $option): bool => ! is_scalar($option) && $option !== null) !== []) {
                $this->problems[] = __(':label has a choice that is not text.', ['label' => $label]);
                $rawOptions = [];
            }

            $options = PossessionFieldOptions::clean($kind, $rawOptions);

            if ($options !== null && count($options) > PossessionFieldOptions::MAX_OPTIONS) {
                $this->problems[] = __(':label has too many choices.', ['label' => $label]);
            }

            if ($options !== null && array_filter($options, fn (string $option): bool => mb_strlen($option) > PossessionFieldOptions::MAX_LENGTH) !== []) {
                $this->problems[] = __(':label has a choice longer than :max characters.', ['label' => $label, 'max' => PossessionFieldOptions::MAX_LENGTH]);
            }

            if ($options !== null && count(array_unique(array_map(mb_strtolower(...), $options))) !== count($options)) {
                $this->problems[] = __(':label repeats a choice.', ['label' => $label]);
            }

            $read[] = ['name' => $name, 'kind' => $kind, 'options' => $options];
        }

        return $read;
    }
}
