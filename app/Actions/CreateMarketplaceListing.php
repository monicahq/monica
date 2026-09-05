<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MarketplaceListingStatusEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\CategoryIsNotActive;
use App\Exceptions\ListingHasNothingToPublish;
use App\Helpers\SlugBuilder;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceListing;
use App\Models\Section;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Prepare something built in an account to be offered to everybody else.
 */
class CreateMarketplaceListing
{
    private MarketplaceListing $listing;

    public function __construct(
        private readonly User $user,
        private readonly Template|Section $listable,
        private readonly MarketplaceCategory $category,
        private string $name,
        private string $summary,
        private string $description,
        private string $authorName,
        private ?string $slug = null,
        private readonly ?string $locale = null,
    ) {}

    public function execute(): MarketplaceListing
    {
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->listing;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->summary = TextSanitizer::plainText($this->summary);
        $this->description = TextSanitizer::plainText($this->description);
        $this->authorName = TextSanitizer::plainText($this->authorName);
        $this->slug = TextSanitizer::nullablePlainText($this->slug);
    }

    private function validate(): void
    {
        if ($this->listable->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This structure is in another account.');
        }

        if (! $this->category->is_active) {
            throw new CategoryIsNotActive("Nothing new can be published into {$this->category->name}.");
        }

        if ($this->listable->published_version_id === null) {
            throw new ListingHasNothingToPublish("{$this->listable->name} has never been published, so there is nothing to offer.");
        }
    }

    private function create(): void
    {
        $this->listing = MarketplaceListing::query()->create([
            'account_id' => $this->user->account_id,
            'marketplace_category_id' => $this->category->id,
            'listable_type' => $this->listable->getMorphClass(),
            'listable_id' => $this->listable->id,
            'slug' => $this->slug(),
            'name' => $this->name,
            'summary' => $this->summary,
            'description' => $this->description,
            'locale' => $this->locale ?? $this->listable->locale,
            'author_name' => $this->authorName,
            'status' => MarketplaceListingStatusEnum::Draft,
            'published_version_id' => null,
            'install_count' => 0,
        ]);
    }

    private function slug(): string
    {
        return SlugBuilder::build(
            wanted: $this->slug,
            derivedFrom: $this->name,
            maxLength: MarketplaceListing::SLUG_LENGTH,
            whenEmpty: 'listing',
            isTaken: fn (string $slug): bool => MarketplaceListing::query()
                ->withTrashed()
                ->where('slug', $slug)
                ->exists(),
        );
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::MarketplaceListingCreation,
            parameters: ['name' => $this->listing->name],
            loggable: $this->listing,
        )->onQueue('low');
    }
}
