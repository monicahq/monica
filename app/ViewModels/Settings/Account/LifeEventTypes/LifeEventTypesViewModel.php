<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\LifeEventTypes;

use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Support\Collection;

class LifeEventTypesViewModel
{
    /** @var Collection<int, LifeEventCategory>|null */
    private ?Collection $categories = null;

    public function __construct(
        private readonly User $user,
        private readonly ?LifeEventCategory $selected = null,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    /**
     * The rail: one entry per category, in the order the pickers will offer them.
     *
     * @return array<int, array{id: int, name: string, count: int, isActive: bool, isSystem: bool, isSelected: bool, isFirst: bool, isLast: bool, position: int, previous: ?string, next: ?string, url: string}>
     */
    public function rail(): array
    {
        $categories = $this->query();
        $last = $categories->count() - 1;

        return $categories
            ->values()
            ->map(fn (LifeEventCategory $category, int $index): array => [
                'id' => $category->id,
                'name' => $category->name,
                'count' => $category->lifeEventTypes->count(),
                'isActive' => $category->is_active,
                'isSystem' => $category->is_system,
                'isSelected' => $this->selected?->is($category) ?? false,
                'isFirst' => $index === 0,
                'isLast' => $index === $last,
                'position' => $index + 1,
                'previous' => $index === 0 ? null : $categories[$index - 1]->name,
                'next' => $index === $last ? null : $categories[$index + 1]->name,
                'url' => route('settings.lifeEventTypes.category', $category),
            ])
            ->all();
    }

    /**
     * Whether the screen is showing every type of the account rather than one category.
     */
    public function showsEveryType(): bool
    {
        return $this->selected === null;
    }

    public function totalCount(): int
    {
        return $this->query()->sum(fn (LifeEventCategory $category): int => $category->lifeEventTypes->count());
    }

    /**
     * What the panel on the right is called.
     */
    public function panelTitle(): string
    {
        return $this->selected->name ?? __('Every type');
    }

    public function selectedIsInactive(): bool
    {
        return $this->selected !== null && ! $this->selected->is_active;
    }

    /**
     * @return array{id: int, name: string, isSystem: bool, isActive: bool, isEmpty: bool, position: int, isFirst: bool, isLast: bool, previous: ?string, next: ?string, activeTypeCount: int}|null
     */
    public function selectedCategory(): ?array
    {
        if ($this->selected === null) {
            return null;
        }

        $entry = collect($this->rail())->firstWhere('id', $this->selected->id);
        $types = $this->query()->firstWhere('id', $this->selected->id)->lifeEventTypes ?? collect();

        return [
            'id' => $this->selected->id,
            'name' => $this->selected->name,
            'isSystem' => $this->selected->is_system,
            'isActive' => $this->selected->is_active,
            'isEmpty' => $types->isEmpty(),
            'position' => $entry['position'],
            'isFirst' => $entry['isFirst'],
            'isLast' => $entry['isLast'],
            'previous' => $entry['previous'],
            'next' => $entry['next'],
            'activeTypeCount' => $types->where('is_active', true)->count(),
        ];
    }

    /**
     * The rows of the panel. One category shows its own types; every type shows
     * them all, grouped by the category order and then by their own.
     *
     * @return array<int, array{id: int, name: string, description: ?string, icon: ?string, colour: ?string, isSystem: bool, isActive: bool, category: ?string, position: int, isFirst: bool, isLast: bool, previous: ?string, next: ?string, url: string}>
     */
    public function rows(): array
    {
        $types = $this->selected === null
            ? $this->query()->flatMap(fn (LifeEventCategory $category): Collection => $category->lifeEventTypes)
            : $this->query()->firstWhere('id', $this->selected->id)->lifeEventTypes ?? collect();

        $types = $types->values();
        $last = $types->count() - 1;

        return $types
            ->map(fn (LifeEventType $type, int $index): array => [
                'id' => $type->id,
                'name' => $type->name,
                'description' => $type->description,
                'icon' => $type->icon,
                'colour' => $type->colour,
                'isSystem' => $type->is_system,
                'isActive' => $type->is_active,
                'category' => $this->selected === null ? $type->lifeEventCategory->name : null,
                'position' => $index + 1,
                'isFirst' => $index === 0,
                'isLast' => $index === $last,
                'previous' => $index === 0 ? null : $types[$index - 1]->name,
                'next' => $index === $last ? null : $types[$index + 1]->name,
                'url' => route('settings.lifeEventTypes.edit', $type),
            ])
            ->all();
    }

    /**
     * Where Add a type should land, so a type added from a category is filed there.
     */
    public function addUrl(): string
    {
        if ($this->selected === null) {
            return route('settings.lifeEventTypes.new');
        }

        return route('settings.lifeEventTypes.new', ['category' => $this->selected->id]);
    }

    public function listUrl(): string
    {
        return $this->selected === null
            ? route('settings.lifeEventTypes.index')
            : route('settings.lifeEventTypes.category', $this->selected);
    }

    /** @return Collection<int, LifeEventCategory> */
    private function query(): Collection
    {
        return $this->categories ??= LifeEventCategory::query()
            ->where('account_id', $this->user->account_id)
            ->with(['lifeEventTypes' => fn ($query) => $query->orderBy('position')])
            ->orderBy('position')
            ->get();
    }
}
