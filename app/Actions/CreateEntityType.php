<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\SlugBuilder;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\User;

/**
 * Declare a kind of object the account wants to keep, when nothing the product ships with fits:
 * Animal, Company, Recipe, Wine bottle.
 */
class CreateEntityType
{
    private EntityType $entityType;

    public function __construct(
        private readonly User $user,
        private string $nameSingular,
        private string $namePlural,
        private ?string $description = null,
        private ?string $icon = null,
        private ?string $colour = null,
        private readonly bool $isActive = true,
        private ?string $slug = null,
    ) {}

    public function execute(): EntityType
    {
        $this->sanitize();
        $this->create();
        $this->log();

        return $this->entityType;
    }

    private function sanitize(): void
    {
        $this->nameSingular = TextSanitizer::plainText($this->nameSingular);
        $this->namePlural = TextSanitizer::plainText($this->namePlural);
        $this->description = TextSanitizer::nullablePlainText($this->description);
        $this->icon = TextSanitizer::nullablePlainText($this->icon) ?? EntityType::DEFAULT_ICON;
        $this->colour = mb_strtolower(TextSanitizer::nullablePlainText($this->colour) ?? EntityType::DEFAULT_COLOUR);
        $this->slug = TextSanitizer::nullablePlainText($this->slug);
    }

    private function create(): void
    {
        $positions = EntityType::query()
            ->where('account_id', $this->user->account_id)
            ->pluck('position');

        $this->entityType = EntityType::query()->create([
            'account_id' => $this->user->account_id,
            'name_singular' => $this->nameSingular,
            'name_plural' => $this->namePlural,
            'slug' => $this->slug(),
            'description' => $this->description,
            'icon' => $this->icon,
            'colour' => $this->colour,
            'position' => ($positions->max() ?? 0) + 1,
            'is_active' => $this->isActive,
            'entity_count' => 0,
        ]);
    }

    private function slug(): string
    {
        return SlugBuilder::build(
            wanted: $this->slug,
            derivedFrom: $this->nameSingular,
            maxLength: EntityType::SLUG_LENGTH,
            whenEmpty: 'type',
            isTaken: fn (string $slug): bool => EntityType::query()
                ->withTrashed()
                ->where('account_id', $this->user->account_id)
                ->where('slug', $slug)
                ->exists(),
        );
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::EntityTypeCreation,
            parameters: ['name' => $this->entityType->name_plural],
            loggable: $this->entityType,
        )->onQueue('low');
    }
}
