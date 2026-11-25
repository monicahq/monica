<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ContactGroupingEnum;
use App\Enums\ContactListColumnSourceEnum;
use App\Enums\ContactSortEnum;
use App\Enums\SortDirectionEnum;
use App\Enums\ValueListEnum;
use App\Helpers\ContactListCatalogue;
use App\Helpers\ContactListCells;
use App\Helpers\CountedPhraseWordings;
use App\Helpers\PageSize;
use App\Models\Contact;
use App\Models\ContactListColumn;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ContactsViewModel
{
    public const int PER_PAGE = 50;

    /** @var LengthAwarePaginator<int, Contact>|null */
    private ?LengthAwarePaginator $contacts = null;

    /** @var list<ContactListColumn>|null */
    private ?array $columns = null;

    /**
     * @param  list<int>  $categoryIds
     * @param  array{category: string|null, changed: int, unchanged: list<string>, previous: array<string, list<int>>}|null  $categoryChange
     */
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly string $query = '',
        private readonly ContactGroupingEnum $grouping = ContactGroupingEnum::Everyone,
        private readonly ContactSortEnum $sort = ContactSortEnum::Name,
        private readonly SortDirectionEnum $direction = SortDirectionEnum::Ascending,
        private readonly int|string $size = self::PER_PAGE,
        private readonly array $categoryIds = [],
        private readonly ?array $categoryChange = null,
    ) {}

    public function size(): int|string
    {
        return $this->size;
    }

    public function defaultSize(): int
    {
        return self::PER_PAGE;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function title(): string
    {
        return __('People');
    }

    public function headcount(): int
    {
        return $this->countOf(ContactGroupingEnum::Everyone);
    }

    /**
     * The headings of the table, the name first. A heading the list can be ordered by links to that
     * order, and the one it is ordered by says which way round.
     *
     * @return list<array{label: string, url: string|null, ariaSort: string|null}>
     */
    public function headings(): array
    {
        $headings = [$this->heading(__('Name'), ContactSortEnum::Name)];

        foreach ($this->columns() as $column) {
            $headings[] = $this->heading($this->catalogue()->headingOf($column), $column->source->sort());
        }

        return $headings;
    }

    /**
     * The people on this page, in runs. Everybody puts the favourites first, under a heading of
     * their own, and a page opening in the middle of a run repeats no heading.
     *
     * @return list<array{label: string|null, count: int|null, people: list<array{name: string, isFavourite: bool, isArchived: bool, showsStar: bool, avatarUrl: string|null, url: string, cells: list<array{values: list<string>, whenEmpty: string, moreLabel: string|null}>}>}>
     */
    public function groups(): array
    {
        $contacts = $this->contacts()->items();
        $cells = new ContactListCells(
            user: $this->user,
            vault: $this->vault,
            columns: $this->columns(),
            contacts: $contacts,
        );

        $favouriteBefore = $this->splitsFavourites() ? $this->favouriteBefore() : null;
        $groups = [];
        $openRun = null;

        foreach ($contacts as $contact) {
            $run = $this->splitsFavourites() ? $contact->is_favourite : null;

            if ($groups === [] || $run !== $openRun) {
                $continuesThePreviousPage = $groups === [] && $run !== null && $run === $favouriteBefore;

                $groups[] = [
                    'label' => $run === null || $continuesThePreviousPage ? null : ($run ? __('Favourites') : __('Everybody else')),
                    'count' => $run === null || $continuesThePreviousPage ? null : $this->runCount($run),
                    'people' => [],
                ];
                $openRun = $run;
            }

            $groups[array_key_last($groups)]['people'][] = $this->rowFor($contact, $cells->for($contact));
        }

        return $groups;
    }

    /**
     * @param  list<list<string>>  $cells
     * @return array{uuid: string, name: string, isFavourite: bool, isArchived: bool, showsStar: bool, avatarUrl: string|null, url: string, cells: list<array{values: list<string>, whenEmpty: string, moreLabel: string|null}>}
     */
    private function rowFor(Contact $contact, array $cells): array
    {
        $columns = $this->columns();

        return [
            'uuid' => $contact->uuid,
            'name' => $contact->name,
            'isFavourite' => $contact->is_favourite,
            'isArchived' => $contact->is_archived,
            'showsStar' => $contact->is_favourite && ! $this->splitsFavourites(),
            'avatarUrl' => $contact->avatar === null
                ? null
                : route('files.variants.show', ['vault' => $this->vault, 'file' => $contact->avatar, 'size' => Contact::AVATAR_THUMBNAIL_PIXELS]),
            'url' => route('contacts.show', ['vault' => $this->vault, 'contact' => $contact]),
            'cells' => array_map(fn (array $values, ContactListColumn $column): array => [
                'values' => $values,
                'whenEmpty' => $column->source === ContactListColumnSourceEnum::LastSpoke ? __('Not yet') : '—',
                'moreLabel' => count($values) > 1 ? trans_choice('Show :count more|Show :count more', count($values) - 1) : null,
            ], $cells, $columns),
        ];
    }

    /**
     * The columns the table shows after the name: the ones the vault saved, or the defaults, less
     * those hidden and those no template supplies any more.
     *
     * @return list<ContactListColumn>
     */
    public function columns(): array
    {
        return $this->columns ??= $this->catalogue()->shownColumnsOf($this->vault);
    }

    /** @return array<string, string> */
    public function selectionWordings(): array
    {
        return CountedPhraseWordings::of(':count selected|:count selected');
    }

    /** @return list<array{id: int, label: string}> */
    public function menuCategories(): array
    {
        return ValueListItem::query()
            ->where('account_id', $this->user->account_id)
            ->whereIn('value_list_id', ValueList::query()
                ->where('account_id', $this->user->account_id)
                ->where('key', ValueListEnum::ContactCategories->value)
                ->select('id'))
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->map(fn (ValueListItem $item): array => ['id' => $item->id, 'label' => $item->label])
            ->all();
    }

    public function categoryChangeUrl(): string
    {
        return route('contacts.categories.update', $this->vault);
    }

    /** @return array{line: string|null, unchangedLine: string|null, restorations: list<array{contact: string, categories: list<int>}>}|null */
    public function categoryChange(): ?array
    {
        if ($this->categoryChange === null) {
            return null;
        }

        $changed = $this->categoryChange['changed'];
        $unchanged = $this->categoryChange['unchanged'];
        $line = null;
        $unchangedLine = null;

        if ($changed > 0) {
            $line = $this->categoryChange['category'] === null
                ? trans_choice('Category removed from :count person|Category removed from :count people', $changed)
                : trans_choice(':count person moved to :category|:count people moved to :category', $changed, ['category' => $this->categoryChange['category']]);
        }

        if ($unchanged !== []) {
            $unchangedLine = trim(trans_choice(':count person was left as they were.|:count people were left as they were.', count($unchanged)).' '.implode(' ', array_unique($unchanged)));
        }

        $restorations = [];

        foreach ($this->categoryChange['previous'] as $uuid => $categories) {
            $restorations[] = ['contact' => (string) $uuid, 'categories' => $categories];
        }

        return ['line' => $line, 'unchangedLine' => $unchangedLine, 'restorations' => $restorations];
    }

    public function customiseUrl(): string
    {
        return route('contactListColumns.edit', $this->vault);
    }

    /** @return array{label: string, url: string|null, ariaSort: string|null} */
    private function heading(string $label, ?ContactSortEnum $sort): array
    {
        if ($sort === null) {
            return ['label' => $label, 'url' => null, 'ariaSort' => null];
        }

        $isCurrent = $sort === $this->sort;
        $firstWay = $sort->isChronological() ? SortDirectionEnum::Descending : SortDirectionEnum::Ascending;
        $otherWay = $this->direction === SortDirectionEnum::Ascending ? SortDirectionEnum::Descending : SortDirectionEnum::Ascending;

        return [
            'label' => $label,
            'url' => route('contacts.index', [
                'vault' => $this->vault,
                'filter' => $this->grouping->value,
                'query' => $this->query === '' ? null : $this->query,
                'sort' => $sort->value,
                'direction' => ($isCurrent ? $otherWay : $firstWay)->value,
                'size' => $this->sizeInUrl(),
                'categories' => $this->categoryIds === [] ? null : $this->categoryIds,
            ]),
            'ariaSort' => $isCurrent ? ($this->direction === SortDirectionEnum::Ascending ? 'ascending' : 'descending') : null,
        ];
    }

    private function catalogue(): ContactListCatalogue
    {
        return once(fn (): ContactListCatalogue => new ContactListCatalogue($this->vault->account));
    }

    /**
     * @return array<int, array{key: string, label: string, icon: string, count: int|null, url: string, isCurrent: bool}>
     */
    public function filters(): array
    {
        return array_map(fn (ContactGroupingEnum $grouping): array => [
            'key' => $grouping->value,
            'label' => __($grouping->label()),
            'icon' => $grouping->icon(),
            'count' => $this->countOf($grouping) === 0 ? null : $this->countOf($grouping),
            'url' => $this->url(grouping: $grouping),
            'isCurrent' => $grouping === $this->grouping,
        ], ContactGroupingEnum::cases());
    }

    /**
     * The categories somebody in the grouping holds, offered to narrow the list, with how many
     * people hold each and whether the list is narrowed to it.
     *
     * @return list<array{id: int, label: string, count: int, isChosen: bool}>
     */
    public function categories(): array
    {
        return array_map(fn (array $held): array => [
            'id' => $held['item']->id,
            'label' => $held['item']->label,
            'count' => $held['contacts'],
            'isChosen' => in_array($held['item']->id, $this->categoryIds, true),
        ], $this->vault->categoriesHeldByContacts($this->grouping, reachesSensitiveFields: true));
    }

    /** @return list<int> */
    public function categoryIds(): array
    {
        return $this->categoryIds;
    }

    public function resultLine(): string
    {
        if (! $this->isNarrowed()) {
            return '';
        }

        return trans_choice(
            ':count of :total shown.|:count of :total shown.',
            $this->matchingCount(),
            ['total' => $this->headcount()],
        );
    }

    public function vaultIsEmpty(): bool
    {
        return ! $this->isNarrowed() && $this->matchingCount() === 0;
    }

    public function noResults(): bool
    {
        return $this->isNarrowed() && $this->matchingCount() === 0;
    }

    public function emptyLine(): string
    {
        if ($this->waitingCount() === 0) {
            return __('This vault holds nobody so far. Everybody you write down here appears on this list.');
        }

        return trans_choice('{1}One person here is a partial contact and stays off this list until they are made a full contact.|:count people here are partial contacts and stay off this list until they are made full contacts.', $this->waitingCount());
    }

    private function waitingCount(): int
    {
        return once(fn (): int => Contact::query()
            ->where('vault_id', $this->vault->id)
            ->where('is_partial', true)
            ->count());
    }

    public function query(): string
    {
        return $this->query;
    }

    public function filter(): string
    {
        return $this->grouping->value;
    }

    public function sort(): string
    {
        return $this->sort->value;
    }

    public function direction(): string
    {
        return $this->direction->value;
    }

    public function newContactUrl(): string
    {
        return route('contacts.new', $this->vault);
    }

    /**
     * What the free offer allows, said only when the account is at it. The control stays on the
     * screen and says why, rather than disappearing and leaving nothing to understand.
     */
    public function ceilingMessage(): ?string
    {
        $response = $this->user->inspect('create', Contact::class);

        return $response->denied() ? (string) $response->message() : null;
    }

    public function vaultUrl(): string
    {
        return route('vaults.show', $this->vault);
    }

    /** @return LengthAwarePaginator<int, Contact> */
    public function contacts(): LengthAwarePaginator
    {
        return $this->contacts ??= PageSize::paginate(
            $this->ordered()->with(['structureVersion.structure.owner', 'avatar']),
            $this->size,
        );
    }

    /** @return Builder<Contact> */
    private function ordered(): Builder
    {
        return $this->matching()
            ->when($this->splitsFavourites(), fn (Builder $query): Builder => $query->orderByDesc('is_favourite'))
            ->sortedBy($this->sort, $this->direction);
    }

    /** @return Builder<Contact> */
    private function matching(): Builder
    {
        return Contact::query()
            ->where('vault_id', $this->vault->id)
            ->inGrouping($this->grouping)
            ->matchingSearch($this->query)
            ->when($this->categoryIds !== [], fn (Builder $query): Builder => $query->holdingAnyCategory($this->categoryIds, true));
    }

    private function searchTerm(): string
    {
        return mb_trim($this->query);
    }

    private function splitsFavourites(): bool
    {
        return $this->grouping === ContactGroupingEnum::Everyone && $this->runCount(true) > 0;
    }

    private function runCount(bool $favourites): int
    {
        return $this->matchingTally()[(int) $favourites];
    }

    private function favouriteBefore(): ?bool
    {
        if ($this->contacts()->currentPage() === 1) {
            return null;
        }

        $contact = $this->ordered()
            ->skip(($this->contacts()->currentPage() - 1) * $this->contacts()->perPage() - 1)
            ->first();

        return $contact?->is_favourite;
    }

    private function countOf(ContactGroupingEnum $grouping): int
    {
        $tally = $this->vaultTally();

        return match ($grouping) {
            ContactGroupingEnum::Everyone => $tally[0][0] + $tally[0][1],
            ContactGroupingEnum::Favourites => $tally[0][1],
            ContactGroupingEnum::NamesOnly => $tally[1][0] + $tally[1][1],
        };
    }

    private function matchingCount(): int
    {
        return array_sum($this->matchingTally());
    }

    /**
     * How many people the vault holds outside the archive, by whether they are only a name and
     * whether they are a favourite. Every tab counts from this one query.
     *
     * @return array<int, array<int, int>>
     */
    private function vaultTally(): array
    {
        return once(function (): array {
            $tally = [0 => [0 => 0, 1 => 0], 1 => [0 => 0, 1 => 0]];

            $rows = Contact::query()
                ->where('vault_id', $this->vault->id)
                ->where('is_archived', false)
                ->toBase()
                ->select('is_partial', 'is_favourite')
                ->selectRaw('count(*) as aggregate')
                ->groupBy('is_partial', 'is_favourite')
                ->get();

            foreach ($rows as $row) {
                $tally[(int) $row->is_partial][(int) $row->is_favourite] = (int) $row->aggregate;
            }

            return $tally;
        });
    }

    /**
     * How many people the list shows, the search included, by whether they are a favourite.
     *
     * @return array<int, int>
     */
    private function matchingTally(): array
    {
        return once(function (): array {
            $tally = [0 => 0, 1 => 0];

            $rows = $this->matching()
                ->toBase()
                ->select('is_favourite')
                ->selectRaw('count(*) as aggregate')
                ->groupBy('is_favourite')
                ->get();

            foreach ($rows as $row) {
                $tally[(int) $row->is_favourite] = (int) $row->aggregate;
            }

            return $tally;
        });
    }

    private function isNarrowed(): bool
    {
        return $this->searchTerm() !== '' || $this->grouping !== ContactGroupingEnum::Everyone || $this->categoryIds !== [];
    }

    private function url(?ContactGroupingEnum $grouping = null): string
    {
        return route('contacts.index', [
            'vault' => $this->vault,
            'filter' => ($grouping ?? $this->grouping)->value,
            'query' => $this->query === '' ? null : $this->query,
            'sort' => $this->sort->value,
            'direction' => $this->direction->value,
            'size' => $this->sizeInUrl(),
            'categories' => $this->categoryIds === [] ? null : $this->categoryIds,
        ]);
    }

    private function sizeInUrl(): int|string|null
    {
        return $this->size === self::PER_PAGE ? null : $this->size;
    }

    public function searchUrl(): string
    {
        return route('contacts.index', [
            'vault' => $this->vault,
            'filter' => $this->grouping->value,
        ]);
    }
}
