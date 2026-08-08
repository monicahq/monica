<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\EntityTypes;

use App\Models\EntityType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * What the entity types screen shows: every kind of record the account
 * invented, in the order they appear in the vault navigation.
 */
class EntityTypesViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    /**
     * The types of the account, in navigation order.
     *
     * The numeric id travels with each row because the drag keeps its
     * bookkeeping in numbers, while every link and every form goes by slug.
     *
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

    /**
     * The line beside the heading, which says how much the account keeps
     * without making somebody add the rows up.
     */
    public function summary(): string
    {
        $types = $this->query()->count();
        $records = (int) $this->query()->sum('entity_count');

        return __(':types · :records', [
            'types' => $types === 1 ? __('1 type') : __(':count types', ['count' => $types]),
            'records' => $records === 1
                ? __('1 record across all vaults')
                : __(':count records across all vaults', ['count' => $records]),
        ]);
    }

    /**
     * Who is signed in, so the sidebar can show their name and their photo.
     */
    public function user(): User
    {
        return $this->user;
    }

    /**
     * The disc a glyph sits on: the colour of the type, thinned down far
     * enough to read as a background in both themes.
     */
    public static function tint(string $colour): string
    {
        return "color-mix(in srgb, {$colour} 12%, transparent)";
    }

    /**
     * How many records a type holds, in words.
     */
    public static function countLabel(int $count): string
    {
        if ($count === 0) {
            return __('no records');
        }

        if ($count === 1) {
            return __('1 record');
        }

        return __(':count records', ['count' => $count]);
    }

    /**
     * @return Builder<EntityType>
     */
    private function query(): Builder
    {
        return EntityType::query()->where('account_id', $this->user->account_id);
    }
}
