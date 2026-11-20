<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Helpers\PageSize;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Pagination\LengthAwarePaginator;

class EntitiesViewModel
{
    public const int PER_PAGE = 20;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly EntityType $entityType,
        private readonly int|string $size = self::PER_PAGE,
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

    public function addUrl(): string
    {
        return route('entities.new', ['vault' => $this->vault, 'entityType' => $this->entityType]);
    }

    public function addLabel(): string
    {
        return __('Add :name', ['name' => $this->entityType->name_singular]);
    }

    /**
     * Why nothing can be added here, or null when something can. A turned-off type already says so
     * in its notice, which the refused control points at instead of repeating it.
     */
    public function addRefusal(): ?string
    {
        $response = $this->user->inspect('addEntity', [$this->entityType, $this->vault]);

        return $response->denied() ? (string) $response->message() : null;
    }

    public function emptyTitle(): string
    {
        return __('Your :name will line up here', ['name' => $this->entityType->name_plural]);
    }

    public function emptyDescription(): string
    {
        $description = trim((string) $this->entityType->description);

        if ($description !== '') {
            return $description;
        }

        return __('The :name you add to this vault will appear here, one row each.', ['name' => $this->entityType->name_plural]);
    }

    public function firstAddLabel(): string
    {
        return __('Add your first :name', ['name' => $this->entityType->name_singular]);
    }

    public function icon(): string
    {
        return $this->entityType->icon ?? EntityType::DEFAULT_ICON;
    }

    public function colour(): string
    {
        return $this->entityType->colour;
    }

    public function size(): int|string
    {
        return $this->size;
    }

    public function defaultSize(): int
    {
        return self::PER_PAGE;
    }

    /** @return LengthAwarePaginator<int, array{id: int, name: string, url: string, avatarUrl: string|null, isArchived: bool}> */
    public function entities(): LengthAwarePaginator
    {
        $query = Entity::query()
            ->where('vault_id', $this->vault->id)
            ->where('entity_type_id', $this->entityType->id)
            ->with('avatar')
            ->orderBy('sort_name');

        return PageSize::paginate($query, $this->size)
            ->through(fn (Entity $entity): array => [
                'id' => $entity->id,
                'name' => $entity->name,
                'url' => route('entities.show', ['vault' => $this->vault, 'entityType' => $this->entityType, 'entity' => $entity]),
                'avatarUrl' => $entity->avatar === null
                    ? null
                    : route('files.variants.show', ['vault' => $this->vault, 'file' => $entity->avatar, 'size' => Contact::AVATAR_THUMBNAIL_PIXELS]),
                'isArchived' => $entity->is_archived,
            ]);
    }
}
