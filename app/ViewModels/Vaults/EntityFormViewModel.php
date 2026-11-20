<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\FieldTypeEnum;
use App\Helpers\FieldValueFormatter;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Collection;

class EntityFormViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly EntityType $entityType,
        private readonly StructureVersion $version,
        private readonly ?Entity $entity = null,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function title(): string
    {
        if ($this->entity !== null) {
            return __('Edit :name', ['name' => $this->entity->name]);
        }

        return __('New :name', ['name' => $this->entityType->name_singular]);
    }

    public function isEditing(): bool
    {
        return $this->entity !== null;
    }

    public function listName(): string
    {
        return $this->entityType->name_plural;
    }

    public function current(): string
    {
        return "entity-type-{$this->entityType->slug}";
    }

    public function action(): string
    {
        if ($this->entity !== null) {
            return route('entities.update', ['vault' => $this->vault, 'entityType' => $this->entityType, 'entity' => $this->entity]);
        }

        return route('entities.create', ['vault' => $this->vault, 'entityType' => $this->entityType]);
    }

    public function method(): string
    {
        return $this->entity === null ? 'post' : 'put';
    }

    public function cancelUrl(): string
    {
        if ($this->entity !== null) {
            return route('entities.show', ['vault' => $this->vault, 'entityType' => $this->entityType, 'entity' => $this->entity]);
        }

        return $this->listUrl();
    }

    public function listUrl(): string
    {
        return route('entities.index', ['vault' => $this->vault, 'entityType' => $this->entityType]);
    }

    public function structureVersionId(): int
    {
        return $this->version->id;
    }

    /** @return list<array{name: string, url: string, isCurrent: bool}> */
    public function templates(): array
    {
        return once(fn (): array => $this->entityType->versionsOnOfferIn($this->vault)
            ->map(fn (StructureVersion $version): array => [
                'name' => $version->structure->owner->name,
                'url' => route('entities.new', [
                    'vault' => $this->vault,
                    'entityType' => $this->entityType,
                    'template' => $version->structure->owner->slug,
                ]),
                'isCurrent' => $version->id === $this->version->id,
            ])
            ->values()
            ->all());
    }

    public function showsTemplates(): bool
    {
        return $this->entity === null && count($this->templates()) > 1;
    }

    public function name(): string
    {
        return (string) old('name', $this->entity->name ?? '');
    }

    public function holdsAFile(): bool
    {
        return $this->version->inputFields()->contains(fn (Field $field): bool => $field->type->holdsAFile());
    }

    /**
     * The pages of the template in order, each with the sections it places and the fields a screen
     * can fill in. A field starts with what the record holds, or on a new record with the default
     * its template gives it. A value refused a moment ago is written back into its field instead; a
     * file cannot be, so it has to be chosen again.
     *
     * @return list<array{name: string, sections: list<array{name: string, fields: list<array{id: int, field: Field, rawValue: FieldValue|null, formatted: string|null, rows: array<int, array{id: int, fields: array<int, array<string, mixed>>}>}>}>}>
     */
    public function pages(): array
    {
        $assembly = $this->version->assembly;

        return $this->version->pages
            ->map(fn (StructureVersionPage $page): array => [
                'name' => $page->label,
                'sections' => $assembly
                    ->where('structure_version_page_id', $page->id)
                    ->map(fn (StructureVersionSection $section): array => [
                        'name' => $section->title_override ?? $section->sectionVersion->section->name,
                        'fields' => $section->sectionVersion->fields
                            ->whereNull('parent_field_id')
                            ->filter(fn (Field $field): bool => $field->canBeFilledIn())
                            ->map(fn (Field $field): array => [
                                'id' => $field->id,
                                'field' => $field,
                                'rawValue' => $this->startingValueFor($field),
                                'formatted' => $this->formatted($this->startingValueFor($field)),
                                'rows' => $this->rowsOf($field),
                            ])
                            ->values()
                            ->all(),
                    ])
                    ->filter(fn (array $section): bool => $section['fields'] !== [])
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $page): bool => $page['sections'] !== [])
            ->values()
            ->all();
    }

    private function startingValueFor(Field $field): ?FieldValue
    {
        if (session()->hasOldInput()) {
            return $this->oldValueFor($field);
        }

        if ($this->entity !== null) {
            return $this->values()->get($field->id);
        }

        return $this->defaultValueFor($field);
    }

    private function formatted(?FieldValue $value): ?string
    {
        if ($value === null || ! $value->exists) {
            return null;
        }

        return once(fn (): FieldValueFormatter => new FieldValueFormatter($this->user))->format($value);
    }

    /** @return Collection<int, FieldValue> */
    private function values(): Collection
    {
        return once(fn (): Collection => $this->entity === null ? collect() : $this->entity
            ->fieldValues()
            ->attached()
            ->whereNull('field_value_row_id')
            ->with(['field', 'valueListItem', 'file.vault', 'relatedContact', 'relatedEntity'])
            ->get()
            ->keyBy('field_id')
            ->toBase());
    }

    /** @return array<int, array{id: int, fields: array<int, array<string, mixed>>}> */
    private function rowsOf(Field $field): array
    {
        if ($this->entity === null || ! $field->type->isRepeater()) {
            return [];
        }

        $children = $field->children->filter(fn (Field $child): bool => $child->type->storesValue());

        return $this->entity
            ->fieldValueRows()
            ->where('field_id', $field->id)
            ->with(['values' => fn ($query) => $query->attached()->with(['field', 'valueListItem', 'file.vault', 'relatedContact', 'relatedEntity'])])
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (FieldValueRow $row): array => [
                'id' => $row->id,
                'fields' => $children
                    ->map(function (Field $child) use ($row): array {
                        $value = $row->values->firstWhere('field_id', $child->id);

                        return [
                            'id' => $child->id,
                            'label' => $child->label,
                            'help' => $child->help_text,
                            'field' => $child,
                            'rawValue' => $value,
                            'value' => $this->formatted($value),
                        ];
                    })
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    private function defaultValueFor(Field $field): ?FieldValue
    {
        $default = $field->default_value;
        $column = $field->type->valueColumn();

        if ($default === null || $column === null || $field->type->holdsAFile() || $field->type === FieldTypeEnum::Address) {
            return null;
        }

        return new FieldValue([$column => $field->type === FieldTypeEnum::Multiselect ? $default : ($default[0] ?? null)]);
    }

    private function oldValueFor(Field $field): ?FieldValue
    {
        $old = old("fields.{$field->id}");
        $column = $field->type->valueColumn();

        if ($old === null || $column === null || $field->type->holdsAFile() || $field->type->isRepeater()) {
            return null;
        }

        return new FieldValue([$column => $old]);
    }
}
