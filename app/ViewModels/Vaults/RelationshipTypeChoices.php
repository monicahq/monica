<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * The natures somebody may pick, gathered under their categories in the account's order.
 *
 * A withdrawn nature is not offered. The one exception is the nature a link already carries: a
 * correction has to show what the link says today, and saving it unchanged must not fail because
 * the account stopped offering it in the meantime. `$keeping` names that one.
 */
class RelationshipTypeChoices
{
    public function __construct(
        private readonly int $accountId,
        private readonly ?RelationshipType $keeping = null,
    ) {}

    /** @return array<int, array{key: string|null, label: string, types: array<int, array{value: int, label: string, name: string, roleA: string, roleB: string, isSymmetric: bool}>}> */
    public function groups(): array
    {
        return once(function (): array {
            $categories = RelationshipCategory::query()
                ->where('account_id', $this->accountId)
                ->where('is_active', true)
                ->with(['types' => function (Relation $query): void {
                    $query->where(function ($query): void {
                        $query->where('is_active', true)
                            ->when($this->keeping !== null, fn ($query) => $query->orWhereKey($this->keeping->id));
                    });
                }])
                ->orderBy('position')
                ->get();

            return $categories
                ->map(fn (RelationshipCategory $category): array => [
                    'key' => $category->key,
                    'label' => $category->name,
                    'types' => $category->types
                        ->map(fn (RelationshipType $type): array => [
                            'value' => $type->id,
                            'label' => $this->label($type),
                            'name' => $type->name,
                            'roleA' => $type->role_a,
                            'roleB' => $type->role_b,
                            'isSymmetric' => $type->is_symmetric,
                        ])
                        ->values()
                        ->all(),
                ])
                ->filter(fn (array $group): bool => $group['types'] !== [])
                ->values()
                ->all();
        });
    }

    public function any(): bool
    {
        return $this->groups() !== [];
    }

    /** @return array<int, array{roleA: string, roleB: string, isSymmetric: bool}> */
    public function map(): array
    {
        $map = [];

        foreach ($this->groups() as $group) {
            foreach ($group['types'] as $type) {
                $map[$type['value']] = [
                    'roleA' => $type['roleA'],
                    'roleB' => $type['roleB'],
                    'isSymmetric' => $type['isSymmetric'],
                ];
            }
        }

        return $map;
    }

    private function label(RelationshipType $type): string
    {
        if ($type->is_symmetric) {
            return $type->role_a;
        }

        return __(':roleA ↔ :roleB', ['roleA' => $type->role_a, 'roleB' => $type->role_b]);
    }
}
