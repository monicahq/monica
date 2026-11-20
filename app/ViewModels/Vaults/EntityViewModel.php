<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\VaultColourEnum;
use App\Helpers\FieldValueFormatter;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Collection;

class EntityViewModel
{
    private const int HEADER_FACTS = 3;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Entity $entity,
        private readonly ?string $selectedPageKey = null,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function entityType(): EntityType
    {
        return $this->entity->entityType;
    }

    public function title(): string
    {
        return $this->entity->name;
    }

    public function typeName(): string
    {
        return $this->entity->entityType->name_singular;
    }

    public function icon(): string
    {
        return $this->entity->entityType->icon ?? EntityType::DEFAULT_ICON;
    }

    public function colour(): string
    {
        return $this->entity->entityType->colour;
    }

    /**
     * The text colour of the type's mark and caption. A colour the vault palette knows is lifted in
     * the dark theme; the near black one has no lifted shade, so it falls back to the ink.
     */
    public function tone(): string
    {
        return VaultColourEnum::tryFrom($this->entity->entityType->colour)?->iconForeground() ?? 'text-ink';
    }

    public function current(): string
    {
        return "entity-type-{$this->entity->entityType->slug}";
    }

    public function isArchived(): bool
    {
        return $this->entity->is_archived;
    }

    public function listUrl(): string
    {
        return route('entities.index', ['vault' => $this->vault, 'entityType' => $this->entity->entityType]);
    }

    public function editUrl(): string
    {
        return route('entities.edit', ['vault' => $this->vault, 'entityType' => $this->entity->entityType, 'entity' => $this->entity]);
    }

    public function templateLine(): string
    {
        return __(':template, version :number', [
            'template' => $this->template()->name ?? __('Template'),
            'number' => $this->entity->structureVersion->version_number,
        ]);
    }

    public function templateUrl(): ?string
    {
        $template = $this->template();

        return $template === null ? null : route('settings.templateBuilder.index', $template);
    }

    /** @return array<int, array{key: string, label: string, count: int, url: string, isSelected: bool}> */
    public function pages(): array
    {
        $selected = $this->selectedPage();

        return $this->entity->structureVersion->pages
            ->map(fn (StructureVersionPage $page): array => [
                'key' => $page->key,
                'label' => $page->label,
                'count' => $this->fieldsOn($page),
                'url' => route('entities.show', [
                    'vault' => $this->vault,
                    'entityType' => $this->entity->entityType,
                    'entity' => $this->entity,
                    'page' => $page->key,
                ]),
                'isSelected' => $page->is($selected),
            ])
            ->values()
            ->all();
    }

    public function selectedPage(): ?StructureVersionPage
    {
        return once(function (): ?StructureVersionPage {
            $pages = $this->entity->structureVersion->pages;

            if ($this->selectedPageKey === null) {
                return $pages->first();
            }

            return $pages->firstWhere('key', $this->selectedPageKey);
        });
    }

    /** @return array<int, array{id: int, name: string, fields: array<int, array{id: int, label: string, field: Field, rawValue: FieldValue|null, value: string|null, rows: array<int, array{id: int, fields: array<int, array<string, mixed>>}>}>}> */
    public function sections(): array
    {
        $page = $this->selectedPage();

        if ($page === null) {
            return [];
        }

        return $this->entity->structureVersion->assembly
            ->where('structure_version_page_id', $page->id)
            ->values()
            ->map(fn (StructureVersionSection $row): array => [
                'id' => $row->id,
                'name' => $row->title_override ?? $row->sectionVersion->section->name,
                'fields' => $this->storedFields($row->sectionVersion->fields)
                    ->map(fn (Field $field): array => [
                        'id' => $field->id,
                        'label' => $field->label,
                        'field' => $field,
                        'rawValue' => $this->values()->get($field->id),
                        'value' => $this->formatted($this->values()->get($field->id)),
                        'rows' => $this->rowsOf($field),
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    /** @return list<array{label: string, value: string}> */
    public function headerFacts(): array
    {
        return $this->entity->structureVersion->assembly
            ->flatMap(fn (StructureVersionSection $row): Collection => $this->storedFields($row->sectionVersion->fields))
            ->filter(fn (Field $field): bool => $field->is_highlighted)
            ->take(self::HEADER_FACTS)
            ->map(fn (Field $field): array => [
                'label' => $field->label,
                'value' => $this->formatted($this->values()->get($field->id)) ?? __('Not recorded'),
            ])
            ->values()
            ->all();
    }

    private function template(): ?Template
    {
        $owner = $this->entity->structureVersion->structure->owner;

        return $owner instanceof Template ? $owner : null;
    }

    private function fieldsOn(StructureVersionPage $page): int
    {
        return $this->entity->structureVersion->assembly
            ->where('structure_version_page_id', $page->id)
            ->sum(fn (StructureVersionSection $row): int => $this->storedFields($row->sectionVersion->fields)->count());
    }

    /**
     * @param  Collection<int, Field>  $fields
     * @return Collection<int, Field>
     */
    private function storedFields(Collection $fields): Collection
    {
        return $fields
            ->whereNull('parent_field_id')
            ->filter(fn (Field $field): bool => $field->type->storesValue());
    }

    /** @return Collection<int, FieldValue> */
    private function values(): Collection
    {
        return once(fn (): Collection => $this->entity
            ->fieldValues()
            ->attached()
            ->whereNull('field_value_row_id')
            ->with(['field', 'valueListItem', 'file.vault', 'relatedContact.vault', 'relatedEntity.vault', 'relatedEntity.entityType'])
            ->get()
            ->keyBy('field_id')
            ->toBase());
    }

    /** @return Collection<int|string, Collection<int, FieldValueRow>> */
    private function repetitions(): Collection
    {
        return once(fn (): Collection => $this->entity
            ->fieldValueRows()
            ->with(['values' => fn ($query) => $query->attached()->with(['field', 'valueListItem', 'file.vault', 'relatedContact.vault', 'relatedEntity.vault', 'relatedEntity.entityType'])])
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->toBase()
            ->groupBy('field_id'));
    }

    /** @return array<int, array{id: int, fields: array<int, array<string, mixed>>}> */
    private function rowsOf(Field $field): array
    {
        if (! $field->type->isRepeater()) {
            return [];
        }

        $children = $field->children->filter(fn (Field $child): bool => $child->type->storesValue());

        return $this->repetitions()
            ->get($field->id, collect())
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

    private function formatted(?FieldValue $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return once(fn (): FieldValueFormatter => new FieldValueFormatter($this->user))->format($value);
    }
}
