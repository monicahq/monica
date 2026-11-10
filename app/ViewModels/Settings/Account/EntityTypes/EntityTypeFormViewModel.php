<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\EntityTypes;

use App\Models\EntityType;
use App\Models\User;

class EntityTypeFormViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly ?EntityType $entityType = null,
    ) {}

    public function isEdit(): bool
    {
        return $this->entityType !== null;
    }

    public function title(): string
    {
        if ($this->entityType === null) {
            return __('Create a kind of record');
        }

        return __('Edit :name', ['name' => $this->entityType->name_singular]);
    }

    public function description(): ?string
    {
        if ($this->entityType === null) {
            return null;
        }

        return __('Renaming a type changes it everywhere. The slug stays as it is, so the links that already went out keep working.');
    }

    public function action(): string
    {
        if ($this->entityType === null) {
            return route('settings.entityTypes.create');
        }

        return route('settings.entityTypes.update', $this->entityType);
    }

    public function method(): string
    {
        return $this->entityType === null ? 'post' : 'put';
    }

    public function saveLabel(): string
    {
        return $this->entityType === null ? __('Create type') : __('Save changes');
    }

    /**
     * @return array{nameSingular: string, namePlural: string, slug: string, description: string, icon: string, colour: string, isActive: bool}
     */
    public function values(): array
    {
        $stored = $this->stored();

        return [
            'nameSingular' => (string) old('name_singular', $stored['name_singular']),
            'namePlural' => (string) old('name_plural', $stored['name_plural']),
            'slug' => (string) old('slug', $stored['slug']),
            'description' => (string) old('description', $stored['description']),
            'icon' => (string) old('icon', $stored['icon']),
            'colour' => (string) old('colour', $stored['colour']),
            'isActive' => $this->isActive($stored['is_active']),
        ];
    }

    /**
     * @return array{name_singular: string, name_plural: string, slug: string, description: string, icon: string, colour: string, is_active: bool}
     */
    private function stored(): array
    {
        if ($this->entityType === null) {
            return [
                'name_singular' => '',
                'name_plural' => '',
                'slug' => '',
                'description' => '',
                'icon' => EntityType::DEFAULT_ICON,
                'colour' => EntityType::DEFAULT_COLOUR,
                'is_active' => true,
            ];
        }

        return [
            'name_singular' => $this->entityType->name_singular,
            'name_plural' => $this->entityType->name_plural,
            'slug' => $this->entityType->slug,
            'description' => $this->entityType->description ?? '',
            'icon' => $this->entityType->icon ?? EntityType::DEFAULT_ICON,
            'colour' => $this->entityType->colour,
            'is_active' => $this->entityType->is_active,
        ];
    }

    private function isActive(bool $stored): bool
    {
        if (session()->hasOldInput()) {
            return session()->hasOldInput('is_active');
        }

        return $stored;
    }

    public function slug(): ?string
    {
        return $this->entityType?->slug;
    }

    public function slugIsEditable(): bool
    {
        return $this->entityType === null;
    }

    public function slugHelp(): string
    {
        if ($this->entityType === null) {
            return __('Lowercase letters, numbers and dashes only. No spaces, no accents, no capitals. It follows the singular name until you change it, and it cannot be changed once the type exists.');
        }

        return __('Used in URLs and by the API. It cannot be changed now the type exists, so the links that already went out keep working.');
    }

    /** @return array<int, array{name: string, selected: bool}> */
    public function icons(): array
    {
        $current = $this->values()['icon'];

        return array_map(
            fn (string $icon): array => ['name' => $icon, 'selected' => $icon === $current],
            EntityType::ICONS,
        );
    }

    /** @return array<int, array{value: string, selected: bool}> */
    public function colours(): array
    {
        $current = $this->values()['colour'];

        return array_map(
            fn (string $colour): array => ['value' => $colour, 'selected' => $colour === $current],
            EntityType::COLOURS,
        );
    }

    public function recordCount(): ?string
    {
        if ($this->entityType === null) {
            return null;
        }

        return trans_choice(':count record uses this type|:count records use this type', $this->entityType->entity_count);
    }

    public function uuid(): ?string
    {
        return $this->entityType?->uuid;
    }

    public function canBeDeleted(): bool
    {
        return $this->entityType !== null && $this->user->can('delete', $this->entityType);
    }

    public function entityType(): ?EntityType
    {
        return $this->entityType;
    }

    public function user(): User
    {
        return $this->user;
    }
}
