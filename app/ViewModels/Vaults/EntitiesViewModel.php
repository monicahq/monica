<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Entity;
use App\Models\EntityType;
use App\Models\User;
use App\Models\Vault;

class EntitiesViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly EntityType $entityType,
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
        return $this->entityType->name_plural;
    }

    public function current(): string
    {
        return "entity-type-{$this->entityType->slug}";
    }

    public function isActive(): bool
    {
        return $this->entityType->is_active;
    }

    public function emptyTitle(): string
    {
        return __('No :name in this vault yet', ['name' => $this->entityType->name_plural]);
    }

    /** @return list<array{id: int, name: string, avatarUrl: string|null, isArchived: bool}> */
    public function entities(): array
    {
        return Entity::query()
            ->where('vault_id', $this->vault->id)
            ->where('entity_type_id', $this->entityType->id)
            ->with('avatar')
            ->orderBy('sort_name')
            ->get()
            ->map(fn (Entity $entity): array => [
                'id' => $entity->id,
                'name' => $entity->name,
                'avatarUrl' => $entity->avatar === null
                    ? null
                    : route('files.show', ['vault' => $this->vault, 'file' => $entity->avatar]),
                'isArchived' => $entity->is_archived,
            ])
            ->values()
            ->all();
    }
}
