<?php

declare(strict_types=1);

namespace App\ViewModels\Marketplace;

use App\Enums\MarketplaceCategoryEnum;
use App\Enums\MarketplaceListingStatusEnum;
use App\Helpers\AvailableLocales;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceInstallation;
use App\Models\MarketplaceListing;
use App\Models\Section;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class MarketplaceViewModel
{
    public const string TYPE_ALL = 'all';

    public const string TYPE_TEMPLATES = 'templates';

    public const string TYPE_SECTIONS = 'sections';

    public const string SORT_UPDATED = 'updated';

    public const string SORT_INSTALLED = 'installed';

    public const string SORT_PUBLISHED = 'published';

    public const string SORT_NAME = 'name';

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
     * @return array<int, array{key: string, name: string, description: string, icon: string, countLabel: string, href: string, isCurrent: bool}>
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
                'countLabel' => trans_choice(':count listing|:count listings', $category->listing_count),
                'href' => $this->urlWith(['category' => $category->key === $this->category() ? null : $category->key]),
                'isCurrent' => $category->key === $this->category(),
            ])
            ->all();
    }

    /**
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

    /** @return array<int, array{value: string, label: string}> */
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
                'label' => (string) AvailableLocales::details($locale)['label'],
            ])
            ->all();
    }

    /** @return array<int, array{value: string, label: string}> */
    public function sorts(): array
    {
        return [
            ['value' => self::SORT_UPDATED, 'label' => __('Recently updated')],
            ['value' => self::SORT_PUBLISHED, 'label' => __('Recently published')],
            ['value' => self::SORT_INSTALLED, 'label' => __('Most installed')],
            ['value' => self::SORT_NAME, 'label' => __('Name')],
        ];
    }

    /** @return array<int, array<string, mixed>> */
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

    public function isCatalogueEmpty(): bool
    {
        return MarketplaceListing::query()
            ->where('status', MarketplaceListingStatusEnum::Published)
            ->doesntExist();
    }

    /** @return array<string, mixed> */
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
            'type' => $isTemplate ? 'template' : 'section',
            'categoryName' => $listing->category->name,
            'facts' => self::facts($listing),
            'authorLabel' => __('By :author', ['author' => $listing->author_name]),
            'language' => (string) AvailableLocales::details($listing->locale)['label'],
            'installLabel' => trans_choice(':count installation|:count installations', $listing->install_count),
            'isInstalled' => $installation !== null,
            'hasUpdate' => $installation?->update_available_version_id !== null,
        ];
    }

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

    /** @return Collection<int, MarketplaceInstallation> */
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

    /** @return Paginator<int, MarketplaceListing> */
    private function page(): Paginator
    {
        return once(function (): Paginator {
            if ($this->sort() !== self::SORT_NAME) {
                return $this->matching()
                    ->with(['category', 'publishedVersion'])
                    ->simplePaginate(self::PER_PAGE)
                    ->withQueryString();
            }

            return $this->pageOrderedByName();
        });
    }

    /** @return Paginator<int, MarketplaceListing> */
    private function pageOrderedByName(): Paginator
    {
        $currentPage = Paginator::resolveCurrentPage();

        $ids = $this->matching()
            ->reorder()
            ->orderBy('id')
            ->get(['id', 'name'])
            ->sortByCollator('name')
            ->slice(($currentPage - 1) * self::PER_PAGE, self::PER_PAGE + 1)
            ->pluck('id')
            ->all();

        $listings = MarketplaceListing::query()
            ->whereIn('id', $ids)
            ->with(['category', 'publishedVersion'])
            ->get()
            ->keyBy('id');

        $items = array_values(array_filter(array_map(
            fn (int $id): ?MarketplaceListing => $listings->get($id),
            $ids,
        )));

        return new Paginator($items, self::PER_PAGE, $currentPage, [
            'path' => Paginator::resolveCurrentPath(),
        ])->withQueryString();
    }

    /** @return Builder<MarketplaceListing> */
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

    /** @return Builder<MarketplaceListing> */
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
            $needle = '%'.mb_strtolower($query).'%';

            $listings->where(function (Builder $listings) use ($needle): void {
                $listings->whereRaw('lower(name) like ?', [$needle])
                    ->orWhereRaw('lower(summary) like ?', [$needle])
                    ->orWhereRaw('lower(author_name) like ?', [$needle]);
            });
        }

        return $listings;
    }

    /** @param  array<string, string|null>  $changes */
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
