<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\EntityTypes;

use App\Models\EntityType;
use App\Models\User;
use App\Models\Vault;
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

    /**
     * @return array{countLabel: string}
     */
    public function contacts(): array
    {
        $count = (int) Vault::query()
            ->where('account_id', $this->user->account_id)
            ->sum('contact_count');

        return [
            'countLabel' => self::countLabel($count),
        ];
    }

    public function hasTypes(): bool
    {
        return $this->query()->exists();
    }

    /**
     * @return array<int, array{icon: string, kind: string, tag: string, name: string, fields: array<int, string>, caption: string}>
     */
    public function examples(): array
    {
        return [
            [
                'icon' => 'profile',
                'kind' => __('Contacts'),
                'tag' => __('Built in'),
                'name' => __('Judy Geller'),
                'fields' => [__('Birthday'), __('Phone'), __('Notes')],
                'caption' => __('Your profiles for people'),
            ],
            [
                'icon' => 'pet',
                'kind' => __('Pets'),
                'tag' => __('Example'),
                'name' => __('Garfield'),
                'fields' => [__('Species'), __('Birthday'), __('Notes')],
                'caption' => __('A profile for each pet'),
            ],
            [
                'icon' => 'organization',
                'kind' => __('Companies'),
                'tag' => __('Example'),
                'name' => __('Central Perk'),
                'fields' => [__('Address'), __('Website'), __('Notes')],
                'caption' => __('A profile for each company'),
            ],
        ];
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
            return __('No profiles yet');
        }

        return trans_choice(':count profile|:count profiles', $count);
    }

    /** @return Builder<EntityType> */
    private function query(): Builder
    {
        return EntityType::query()->where('account_id', $this->user->account_id);
    }
}
