<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\RelationshipTypes;

use App\Enums\RelationshipBehaviorEnum;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Support\Collection;

class RelationshipTypesViewModel
{
    /** @var Collection<int, RelationshipCategory>|null */
    private ?Collection $categories = null;

    public function __construct(
        private readonly User $user,
    ) {}

    /**
     * @return array<int, array{id: int, name: string, position: int, isSystem: bool, isEmpty: bool, isFirst: bool, isLast: bool, previous: ?string, next: ?string, types: array<int, array{id: int, name: string, roleA: string, roleB: string, isSymmetric: bool, directionLabel: string, note: ?string, isSystem: bool, isActive: bool}>}>
     */
    public function categories(): array
    {
        $categories = $this->query();
        $last = $categories->count() - 1;

        return $categories
            ->values()
            ->map(fn (RelationshipCategory $category, int $index): array => [
                'id' => $category->id,
                'name' => $category->name,
                'position' => $index + 1,
                'isSystem' => $category->is_system,
                'isEmpty' => $category->types->isEmpty(),
                'isFirst' => $index === 0,
                'isLast' => $index === $last,
                'previous' => $index === 0 ? null : $categories[$index - 1]->name,
                'next' => $index === $last ? null : $categories[$index + 1]->name,
                'types' => $this->types($category),
            ])
            ->all();
    }

    public function user(): User
    {
        return $this->user;
    }

    /**
     * @return array<int, array{id: int, name: string, roleA: string, roleB: string, isSymmetric: bool, directionLabel: string, note: ?string, isSystem: bool, isActive: bool}>
     */
    private function types(RelationshipCategory $category): array
    {
        return $category->types
            ->map(fn (RelationshipType $type): array => [
                'id' => $type->id,
                'name' => $type->name,
                'roleA' => $type->role_a,
                'roleB' => $type->role_b,
                'isSymmetric' => $type->is_symmetric,
                'directionLabel' => $this->directionLabel($type),
                'note' => $this->note($type),
                'isSystem' => $type->is_system,
                'isActive' => $type->is_active,
            ])
            ->values()
            ->all();
    }

    private function directionLabel(RelationshipType $type): string
    {
        if ($type->is_symmetric) {
            return __('The same from both sides');
        }

        return __('Different on each side');
    }

    private function note(RelationshipType $type): ?string
    {
        if ($type->system_behavior === RelationshipBehaviorEnum::GenealogyParentage) {
            return __('feeds the family tree');
        }

        if ($type->system_behavior === RelationshipBehaviorEnum::Romantic) {
            return __('feeds romantic history');
        }

        if (! $type->is_active) {
            return __('existing relationships still read normally');
        }

        return null;
    }

    /** @return Collection<int, RelationshipCategory> */
    private function query(): Collection
    {
        return $this->categories ??= RelationshipCategory::query()
            ->where('account_id', $this->user->account_id)
            ->with('types')
            ->orderBy('position')
            ->get();
    }
}
