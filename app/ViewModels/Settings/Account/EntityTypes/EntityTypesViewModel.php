<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\EntityTypes;

use App\Models\EntityType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class EntityTypesViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    /**
     * @return array<int, array{id: int, slug: string, nameSingular: string, namePlural: string, description: ?string, icon: string, colour: string, tint: string, isActive: bool, countLabel: string}>
     */
    public function types(): array
    {
        return $this->query()
            ->orderBy('position')
            ->get()
            ->map(fn (EntityType $type): array => [
                'id' => $type->id,
                'slug' => $type->slug,
                'nameSingular' => $type->name_singular,
                'namePlural' => $type->name_plural,
                'description' => $type->description,
                'icon' => $type->icon ?? EntityType::DEFAULT_ICON,
                'colour' => $type->colour,
                'tint' => self::tint($type->colour),
                'isActive' => $type->is_active,
                'countLabel' => self::countLabel($type->entity_count),
            ])
            ->all();
    }

    public function summary(): string
    {
        $types = $this->query()->count();
        $records = (int) $this->query()->sum('entity_count');

        return __(':types · :records', [
            'types' => trans_choice(':count type|:count types', $types),
            'records' => trans_choice(':count record across all vaults|:count records across all vaults', $records),
        ]);
    }

    public function user(): User
    {
        return $this->user;
    }

    public static function tint(string $colour): string
    {
        return "color-mix(in srgb, {$colour} 12%, transparent)";
    }

    public static function countLabel(int $count): string
    {
        if ($count === 0) {
            return __('no records');
        }

        return trans_choice(':count record|:count records', $count);
    }

    /** @return Builder<EntityType> */
    private function query(): Builder
    {
        return EntityType::query()->where('account_id', $this->user->account_id);
    }
}
