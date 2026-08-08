<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Declare a kind of object the account wants to keep, when nothing the product
 * ships with fits: Animal, Company, Recipe, Wine bottle.
 *
 * This writes down the noun and nothing else. What one of them holds is a
 * template, and it is described afterwards, which is why a type can exist for
 * a while with no structure to show.
 *
 * Both names are asked for rather than one being made from the other, since a
 * plural derived in English is wrong in most other languages.
 *
 * The slug is built here and never rebuilt. It travels in URLs and through the
 * API, so a rename must not break a link that already went out.
 *
 * A type can be declared inactive from the start, for somebody setting up the
 * account before anybody is meant to file anything under it.
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

    /**
     * Build a slug from the singular name, and keep it unique within the
     * account by counting up until nothing answers to it.
     *
     * A name written in a script `Str::slug` leaves nothing of, such as one
     * entirely in Japanese, would otherwise produce an empty slug, so the word
     * "type" stands in and the counter does the rest.
     */
    private function slug(): string
    {
        $stem = Str::limit(Str::slug($this->nameSingular), EntityType::SLUG_LENGTH, '') ?: 'type';
        $slug = $stem;
        $suffix = 1;

        while ($this->slugIsTaken($slug)) {
            $suffix++;
            $slug = Str::limit($stem, EntityType::SLUG_LENGTH - mb_strlen("-{$suffix}"), '')."-{$suffix}";
        }

        return $slug;
    }

    private function slugIsTaken(string $slug): bool
    {
        return EntityType::query()
            ->withTrashed()
            ->where('account_id', $this->user->account_id)
            ->where('slug', $slug)
            ->exists();
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
