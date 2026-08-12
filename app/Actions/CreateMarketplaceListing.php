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
 *
 * Nothing reaches the catalogue by being good. It reaches it because somebody
 * decided to offer it, and this is that decision written down. The listing
 * starts as a draft, which is the state of having decided to share without
 * having sent anything yet, and nobody outside the account sees it.
 *
 * A section can be listed on its own, with no template or page around it. That
 * is what lowers the barrier to contributing, and it is why what is listed is a
 * template or a section rather than only the bigger of the two.
 *
 * What travels is a frozen version, so something that only exists as a draft
 * has nothing to send and is refused here rather than at submission, where the
 * author would have written the whole description first.
 *
 * The name, the summary and the description are the catalogue copy and are not
 * the internal ones. Somebody may well call a template "v3 final" in their own
 * account and something readable in public.
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

    /**
     * Settle the slug. It is unique across the whole catalogue rather than
     * within an account, since the catalogue is one public place and a link
     * that already went out cannot later land on somebody else's work. A
     * withdrawn listing keeps its own for the same reason.
     */
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
