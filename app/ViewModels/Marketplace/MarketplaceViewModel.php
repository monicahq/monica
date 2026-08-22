<?php

declare(strict_types=1);

namespace App\ViewModels\Marketplace;

use App\Enums\MarketplaceCategoryEnum;
use App\Enums\MarketplaceListingStatusEnum;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceInstallation;
use App\Models\MarketplaceListing;
use App\Models\Section;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * The front of the marketplace: everything published, and the handful of ways
 * of narrowing it down to the one thing somebody came for.
 *
 * The screen is readable signed out, which is why the user is optional. Nothing
 * here is private: the catalogue is the one public part of the product, and a
 * link to it has to work when it is pasted somewhere. What the user changes is
 * the two marks on a card, Installed and Update available, which are facts
 * about the person looking rather than about the listing.
 *
 * Everything that narrows the list travels in the query string. Five facets
 * that combine freely cannot be path segments without one of them arbitrarily
 * winning the address, and a filtered catalogue that cannot be linked to is a
 * catalogue nobody can send to a friend.
 *
 * Filtering happens in SQL rather than in memory, which is the one thing that
 * separates this from the templates screen it otherwise resembles: that list is
 * one account's rows and this one is the whole instance.
 */
class MarketplaceViewModel
{
    public const string TYPE_ALL = 'all';

    public const string TYPE_TEMPLATES = 'templates';

    public const string TYPE_SECTIONS = 'sections';

    public const string SORT_UPDATED = 'updated';

    public const string SORT_INSTALLED = 'installed';

    public const string SORT_PUBLISHED = 'published';

    public const string SORT_NAME = 'name';

    /**
     * How many cards a page holds. Three columns, eight rows deep.
     */
    public const int PER_PAGE = 24;

    public function __construct(
        private readonly ?User $user = null,
        private readonly string $query = '',
        private readonly string $type = self::TYPE_ALL,
        private readonly string $category = '',
        private readonly string $locale = '',
        private readonly string $sort = self::SORT_UPDATED,
    ) {}

    public function user(): ?User
    {
        return $this->user;
    }

    public function statistics(): MarketplaceStatistics
    {
        return new MarketplaceStatistics;
    }

    /**
     * The categories, which are ours and are the same eight for everybody. Each
     * one is a link back to this screen scoped to it, so a category is a place
     * that can be linked to rather than a control that has to be clicked.
     *
     * @return array<int, array{key: string, name: string, description: string, icon: string, iconClass: string, countLabel: string, href: string, isCurrent: bool}>
     */
    public function categories(): array
    {
        return MarketplaceCategory::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->map(fn (MarketplaceCategory $category): array => [
                'key' => $category->key,
                'name' => $category->name,
                'description' => $category->description,
                'icon' => $category->icon,
                'iconClass' => MarketplaceCategoryEnum::tryFrom($category->key)?->iconClass() ?? 'bg-sand text-muted',
                'countLabel' => trans_choice(':count listing|:count listings', $category->listing_count),
                'href' => $this->urlWith(['category' => $category->key === $this->category() ? null : $category->key]),
                'isCurrent' => $category->key === $this->category(),
            ])
            ->all();
    }

    /**
     * The chips that say what kind of thing to show. There are two kinds of
     * listing and no more: an activity type cannot be published yet, and a chip
     * that filters on nothing is worse than a chip that is missing.
     *
     * @return array<int, array{key: string, label: string, icon: string|null, count: int, href: string, isCurrent: bool}>
     */
    public function types(): array
    {
        $counts = [
            self::TYPE_ALL => $this->scoped()->count(),
            self::TYPE_TEMPLATES => $this->scoped()->where('listable_type', Template::class)->count(),
            self::TYPE_SECTIONS => $this->scoped()->where('listable_type', Section::class)->count(),
        ];

        $labels = [
            self::TYPE_ALL => ['label' => __('All'), 'icon' => null],
            self::TYPE_TEMPLATES => ['label' => __('Templates'), 'icon' => 'file'],
            self::TYPE_SECTIONS => ['label' => __('Sections'), 'icon' => 'note'],
        ];

        return array_map(fn (string $key): array => [
            'key' => $key,
            'label' => $labels[$key]['label'],
            'icon' => $labels[$key]['icon'],
            'count' => $counts[$key],
            'href' => $this->urlWith(['type' => $key === self::TYPE_ALL ? null : $key]),
            'isCurrent' => $key === $this->type(),
        ], array_keys($labels));
    }

    /**
     * The languages the catalogue is actually written in, rather than the ones
     * the interface is translated into: offering a filter that returns nothing
     * is a way of making the catalogue look empty.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function locales(): array
    {
        return MarketplaceListing::query()
            ->where('status', MarketplaceListingStatusEnum::Published)
            ->pluck('locale')
            ->unique()
            ->sort()
            ->values()
            ->map(fn (string $locale): array => [
                'value' => $locale,
                'label' => config("monica.locales.{$locale}.label") ?? $locale,
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function sorts(): array
    {
        return [
            ['value' => self::SORT_UPDATED, 'label' => __('Recently updated')],
            ['value' => self::SORT_PUBLISHED, 'label' => __('Recently published')],
            ['value' => self::SORT_INSTALLED, 'label' => __('Most installed')],
            ['value' => self::SORT_NAME, 'label' => __('Name')],
        ];
    }

    /**
     * The cards.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listings(): array
    {
        return new Collection($this->page()->items())
            ->map(fn (MarketplaceListing $listing): array => $this->card($listing))
            ->all();
    }

    public function resultsTitle(): string
    {
        $count = $this->matching()->count();

        return trans_choice(':count listing|:count listings', $count);
    }

    /**
     * The line under the count, which says what order the cards are in. Without
     * it the first card looks like a recommendation rather than the newest one.
     */
    public function resultsDescription(): string
    {
        return match ($this->sort()) {
            self::SORT_INSTALLED => __('Everything published, most installed first'),
            self::SORT_PUBLISHED => __('Everything published, newest listings first'),
            self::SORT_NAME => __('Everything published, in alphabetical order'),
            default => __('Everything published, newest changes first'),
        };
    }

    public function hasMore(): bool
    {
        return $this->page()->hasMorePages();
    }

    public function nextPageUrl(): ?string
    {
        return $this->page()->nextPageUrl();
    }

    public function showMoreLabel(): string
    {
        return trans_choice('[0,*]Show :count more', self::PER_PAGE);
    }

    public function query(): string
    {
        return $this->query;
    }

    public function type(): string
    {
        return in_array($this->type, [self::TYPE_ALL, self::TYPE_TEMPLATES, self::TYPE_SECTIONS], true)
            ? $this->type
            : self::TYPE_ALL;
    }

    public function category(): string
    {
        return MarketplaceCategoryEnum::tryFrom($this->category) === null ? '' : $this->category;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function sort(): string
    {
        return in_array($this->sort, [self::SORT_UPDATED, self::SORT_INSTALLED, self::SORT_PUBLISHED, self::SORT_NAME], true)
            ? $this->sort
            : self::SORT_UPDATED;
    }

    /**
     * Whether anything at all has been published, which is a different question
     * from whether the current filters match anything. One asks somebody to
     * widen their search and the other asks them to come back later.
     */
    public function isCatalogueEmpty(): bool
    {
        return MarketplaceListing::query()
            ->where('status', MarketplaceListingStatusEnum::Published)
            ->doesntExist();
    }

    /**
     * @return array<string, mixed>
     */
    private function card(MarketplaceListing $listing): array
    {
        $installation = $this->installations()->get($listing->id);
        $isTemplate = $listing->listable_type === Template::class;

        return [
            'slug' => $listing->slug,
            'name' => $listing->name,
            'summary' => $listing->summary,
            'typeLabel' => $isTemplate ? __('Template') : __('Section'),
            'typeIcon' => $isTemplate ? 'file' : 'note',
            'typeIconClass' => $isTemplate ? 'bg-indigo-tint text-indigo-deep' : 'bg-forest-tint text-forest-deep',
            'typeTextClass' => $isTemplate ? 'text-indigo-deep' : 'text-forest-deep',
            'categoryName' => $listing->category->name,
            'facts' => self::facts($listing),
            'authorLabel' => __('By :author', ['author' => $listing->author_name]),
            'language' => config("monica.locales.{$listing->locale}.label") ?? $listing->locale,
            'installLabel' => trans_choice(':count installation|:count installations', $listing->install_count),
            'isInstalled' => $installation !== null,
            'hasUpdate' => $installation?->update_available_version_id !== null,
        ];
    }

    /**
     * What is inside a listing, in one line, read off the version that is
     * published rather than off the author's account: the account may have moved
     * on, and what is offered is what was frozen.
     */
    private static function facts(MarketplaceListing $listing): string
    {
        $payload = $listing->publishedVersion?->payload;

        if ($payload === null) {
            return '';
        }

        if (($payload['type'] ?? null) === 'template') {
            $pages = $payload['template']['pages'] ?? [];
            $sections = array_merge(...array_map(fn (array $page): array => $page['sections'] ?? [], $pages)) ?: [];
            $fields = array_sum(array_map(fn (array $row): int => count($row['section']['fields'] ?? []), $sections));

            return implode(' · ', [
                trans_choice(':count page|:count pages', count($pages)),
                trans_choice(':count section|:count sections', count($sections)),
                trans_choice(':count field|:count fields', $fields),
            ]);
        }

        $fields = count($payload['section']['fields'] ?? []);
        $lists = count($payload['value_lists'] ?? []);

        $parts = [trans_choice(':count field|:count fields', $fields)];

        if ($lists > 0) {
            $parts[] = trans_choice(':count value list|:count value lists', $lists);
        }

        return implode(' · ', $parts);
    }

    /**
     * What the person looking has already installed, by the listing it came
     * from. Signed out there is nothing to know, and the two marks on the cards
     * simply never appear.
     *
     * @return Collection<int, MarketplaceInstallation>
     */
    private function installations(): Collection
    {
        return once(function (): Collection {
            if ($this->user === null) {
                return new Collection;
            }

            return MarketplaceInstallation::query()
                ->where('account_id', $this->user->account_id)
                ->get()
                ->keyBy('marketplace_listing_id');
        });
    }

    /**
     * @return Paginator<int, MarketplaceListing>
     */
    private function page(): Paginator
    {
        return once(fn (): Paginator => $this->matching()
            ->with(['category', 'publishedVersion'])
            ->simplePaginate(self::PER_PAGE)
            ->withQueryString());
    }

    /**
     * Everything published that answers to all of the filters, in the order
     * asked for.
     *
     * @return Builder<MarketplaceListing>
     */
    private function matching(): Builder
    {
        $listings = $this->scoped();

        if ($this->type() === self::TYPE_TEMPLATES) {
            $listings->where('listable_type', Template::class);
        }

        if ($this->type() === self::TYPE_SECTIONS) {
            $listings->where('listable_type', Section::class);
        }

        return match ($this->sort()) {
            self::SORT_INSTALLED => $listings->orderByDesc('install_count'),
            self::SORT_PUBLISHED => $listings->orderByDesc('first_published_at'),
            self::SORT_NAME => $listings->orderBy('name'),
            default => $listings->orderByDesc('updated_at'),
        };
    }

    /**
     * Everything published that answers to every filter except the kind of
     * thing it is. The chips count against this rather than against the whole
     * catalogue, so the number on a chip is what pressing it would show.
     *
     * @return Builder<MarketplaceListing>
     */
    private function scoped(): Builder
    {
        $listings = MarketplaceListing::query()
            ->where('status', MarketplaceListingStatusEnum::Published);

        if ($this->category() !== '') {
            $listings->whereRelation('category', 'key', $this->category());
        }

        if ($this->locale() !== '') {
            $listings->where('locale', $this->locale());
        }

        $query = mb_trim($this->query);

        if ($query !== '') {
            /*
             * Both sides are folded down rather than compared as they are.
             * Whether `like` minds the case is up to the database and its
             * collation, and postgres always does: a catalogue where searching
             * for a template by name only works if you capitalise it the way its
             * author did is a catalogue with no search in it.
             */
            $needle = '%'.mb_strtolower($query).'%';

            $listings->where(function (Builder $listings) use ($needle): void {
                $listings->whereRaw('lower(name) like ?', [$needle])
                    ->orWhereRaw('lower(summary) like ?', [$needle])
                    ->orWhereRaw('lower(author_name) like ?', [$needle]);
            });
        }

        return $listings;
    }

    /**
     * This screen again, with one facet changed and the rest left alone. A chip
     * that dropped the search box on its way past would be a chip nobody uses
     * twice.
     *
     * @param  array<string, string|null>  $changes
     */
    private function urlWith(array $changes): string
    {
        $parameters = array_filter([
            'query' => $this->query,
            'type' => $this->type() === self::TYPE_ALL ? null : $this->type(),
            'category' => $this->category() === '' ? null : $this->category(),
            'locale' => $this->locale() === '' ? null : $this->locale(),
            'sort' => $this->sort() === self::SORT_UPDATED ? null : $this->sort(),
            ...$changes,
        ], fn (?string $value): bool => $value !== null && $value !== '');

        return route('marketplace.index', $parameters);
    }
}
