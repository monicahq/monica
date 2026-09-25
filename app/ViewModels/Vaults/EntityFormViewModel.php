<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\FieldTypeEnum;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\Vault;

class EntityFormViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly EntityType $entityType,
        private readonly StructureVersion $version,
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
        return __('New :name', ['name' => $this->entityType->name_singular]);
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
        return route('entities.create', ['vault' => $this->vault, 'entityType' => $this->entityType]);
    }

    public function listUrl(): string
    {
        return route('entities.index', ['vault' => $this->vault, 'entityType' => $this->entityType]);
    }

    public function structureVersionId(): int
    {
        return $this->version->id;
    }

    public function name(): string
    {
        return (string) old('name', '');
    }

    public function holdsAFile(): bool
    {
        return $this->version->inputFields()->contains(fn (Field $field): bool => $field->type->holdsAFile());
    }

    /**
     * The pages of the template in order, each with the sections it places and the fields a screen
     * can fill in. A field starts with the default its template gives it. A value refused a moment
     * ago is written back into its field instead; a file cannot be, so it has to be chosen again.
     *
     * @return list<array{name: string, sections: list<array{name: string, fields: list<array{id: int, field: Field, rawValue: FieldValue|null}>}>}>
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
                                'rawValue' => session()->hasOldInput() ? $this->oldValueFor($field) : $this->defaultValueFor($field),
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
