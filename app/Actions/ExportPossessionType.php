<?php

declare(strict_types=1);

namespace App\Actions;

use App\Helpers\PossessionTypeFile;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Write a possession type out as a file: its name, its colour, its groups and its fields, in order.
 * The file carries the structure only.
 */
class ExportPossessionType
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionType $possessionType,
    ) {}

    /** @return array{format: string, format_version: int, possession_type: array{name: string, colour: string, icon: string|null, groups: list<array{name: string, fields: list<array<string, mixed>>}>, fields: list<array<string, mixed>>}} */
    public function execute(): array
    {
        $this->validate();

        return [
            'format' => PossessionTypeFile::FORMAT,
            'format_version' => PossessionTypeFile::FORMAT_VERSION,
            'possession_type' => [
                'name' => $this->possessionType->name,
                'colour' => $this->possessionType->colour,
                'icon' => $this->possessionType->icon,
                'groups' => $this->possessionType->groups()->with('fields')->get()
                    ->map(fn (PossessionFieldGroup $group): array => [
                        'name' => $group->name,
                        'fields' => $this->fields($group->fields),
                    ])
                    ->values()
                    ->all(),
                'fields' => $this->fields($this->possessionType->standaloneFields()->get()),
            ],
        ];
    }

    private function validate(): void
    {
        if ($this->possessionType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession type is in another account.');
        }
    }

    /**
     * @param  Collection<int, PossessionField>  $fields
     * @return list<array<string, mixed>>
     */
    private function fields(Collection $fields): array
    {
        return $fields
            ->map(fn (PossessionField $field): array => array_filter([
                'name' => $field->name,
                'kind' => $field->kind->value,
                'options' => $field->options,
            ], fn (mixed $value): bool => $value !== null))
            ->values()
            ->all();
    }
}
