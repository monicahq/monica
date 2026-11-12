<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\PossessionFieldKindEnum;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;

/**
 * The fields one possession type brings to the Add a possession form: its groups in order, each with
 * its fields, then the fields standing outside any group, each with what was typed before a refused
 * save.
 */
class PossessionTypeFieldsBlock
{
    /** @param  array<string, mixed>  $oldValues  keyed by the uuid of the field */
    public function __construct(
        private readonly PossessionType $possessionType,
        private readonly array $oldValues = [],
    ) {}

    public function typeName(): string
    {
        return $this->possessionType->name;
    }

    public function title(): string
    {
        return __('Fields from the :type type', ['type' => $this->possessionType->name]);
    }

    public function note(): string
    {
        return __('All optional. Every :type in your account has them.', ['type' => mb_strtolower($this->possessionType->name)]);
    }

    public function isEmpty(): bool
    {
        return $this->groups() === [] && $this->standaloneFields() === [];
    }

    /** @return list<array{name: string, fields: list<array<string, mixed>>}> */
    public function groups(): array
    {
        return once(fn (): array => $this->possessionType->groups()
            ->with('fields')
            ->get()
            ->map(fn (PossessionFieldGroup $group): array => [
                'name' => $group->name,
                'fields' => $group->fields->map($this->field(...))->values()->all(),
            ])
            ->filter(fn (array $group): bool => $group['fields'] !== [])
            ->values()
            ->all());
    }

    /** @return list<array<string, mixed>> */
    public function standaloneFields(): array
    {
        return once(fn (): array => $this->possessionType->standaloneFields()
            ->get()
            ->map($this->field(...))
            ->values()
            ->all());
    }

    /** @return array{uuid: string, id: string, name: string, kind: PossessionFieldKindEnum, options: list<string>, inputName: string, errorKey: string, old: string} */
    private function field(PossessionField $field): array
    {
        $old = $this->oldValues[$field->uuid] ?? '';

        return [
            'uuid' => $field->uuid,
            'id' => "possession-field-{$field->uuid}",
            'name' => $field->name,
            'kind' => $field->kind,
            'options' => $field->options ?? [],
            'inputName' => "values[{$field->uuid}]",
            'errorKey' => "values.{$field->uuid}",
            'old' => is_scalar($old) ? (string) $old : '',
        ];
    }
}
