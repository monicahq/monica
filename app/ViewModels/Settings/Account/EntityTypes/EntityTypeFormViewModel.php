<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\EntityTypes;

use App\Models\EntityType;
use App\Models\User;

/**
 * The form that declares a kind of record, or changes one that exists. It is
 * one screen serving both, because naming a type and renaming it ask for the
 * same things.
 *
 * What was typed wins over what is stored, so a form that came back rejected
 * shows the words somebody wrote rather than quietly reverting them.
 */
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
            return __('Create an entity type');
        }

        return __('Edit :name', ['name' => $this->entityType->name_singular]);
    }

    public function description(): string
    {
        if ($this->entityType === null) {
            return __('Name the thing you want to keep track of, decide how it looks, and it appears in every vault in this account.');
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
     * What the fields start out holding.
     *
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
     * What the fields hold before anybody has typed: a type that exists, or
     * the defaults a new one starts on.
     *
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

    /**
     * Whether the checkbox is ticked. A checkbox nobody ticked sends nothing
     * at all, so `old('is_active')` reads the same whether it was cleared or
     * never asked for. Whether there is any old input to speak of is what
     * tells the two apart.
     */
    private function isActive(bool $stored): bool
    {
        if (session()->hasOldInput()) {
            return session()->hasOldInput('is_active');
        }

        return $stored;
    }

    /**
     * The slug a type already has. A type being created has none: the server
     * settles it on save, and the screen shows a guess until then.
     */
    public function slug(): ?string
    {
        return $this->entityType?->slug;
    }

    /**
     * Whether the slug can still be typed. It can be chosen while the type is
     * being declared and never again, since it is what URLs and the API were
     * given and a link that already went out has to keep working.
     */
    public function slugIsEditable(): bool
    {
        return $this->entityType === null;
    }

    public function slugHelp(): string
    {
        if ($this->entityType === null) {
            return __('Lowercase letters, numbers and dashes only: no spaces, no accents, no capitals. It follows the singular name until you change it, and it cannot be changed once the type exists.');
        }

        return __('Used in URLs and by the API. It cannot be changed now the type exists, so the links that already went out keep working.');
    }

    /**
     * The glyphs on offer, with the one in use marked.
     *
     * @return array<int, array{name: string, selected: bool}>
     */
    public function icons(): array
    {
        $current = $this->values()['icon'];

        return array_map(
            fn (string $icon): array => ['name' => $icon, 'selected' => $icon === $current],
            EntityType::ICONS,
        );
    }

    /**
     * The colours on offer, with the one in use marked.
     *
     * @return array<int, array{value: string, selected: bool}>
     */
    public function colours(): array
    {
        $current = $this->values()['colour'];

        return array_map(
            fn (string $colour): array => ['value' => $colour, 'selected' => $colour === $current],
            EntityType::COLOURS,
        );
    }

    /**
     * The two things about a type that exist only once it does: how much it
     * holds, and the identifier the API knows it by.
     */
    public function recordCount(): ?string
    {
        if ($this->entityType === null) {
            return null;
        }

        if ($this->entityType->entity_count === 1) {
            return __('1 record uses this type');
        }

        return __(':count records use this type', ['count' => $this->entityType->entity_count]);
    }

    public function uuid(): ?string
    {
        return $this->entityType?->uuid;
    }

    /**
     * Whether the type may be deleted, which it may only be while it holds
     * nothing. Otherwise the records go first, deliberately.
     */
    public function canBeDeleted(): bool
    {
        return $this->entityType !== null && $this->entityType->entity_count === 0;
    }

    public function entityType(): ?EntityType
    {
        return $this->entityType;
    }

    /**
     * Who is signed in, so the sidebar can show their name and their photo.
     */
    public function user(): User
    {
        return $this->user;
    }
}
