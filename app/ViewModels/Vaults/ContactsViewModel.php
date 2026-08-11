<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;

/**
 * What the contacts screen shows: everybody in one vault, on one list, narrowed
 * by what was typed and by which chip is in force, in the order asked for.
 *
 * The table is deliberately short of columns. A contact carries a name, a
 * nickname, the template version its page renders from and the two dates the
 * row is worth sorting on, and that is all there is to show: labels, how you
 * know somebody, their birthday, their address and what you owe them are each
 * their own model and none of them exists. The screen leaves them out rather
 * than drawing a column of em-dashes, since a column that is empty for months
 * is read as a fault.
 *
 * Last spoke is the one exception, and it is a real column: nothing writes
 * `last_activity_at` yet because activities are a later model, so it reads as
 * unknown on every row today. It is here because it is what somebody sorts this
 * list by, and it comes to life on its own the day activities land.
 *
 * Everything the URL carries is checked against the constants below before it
 * reaches the query, so a hand-edited address narrows the list to nothing at
 * worst.
 */
class ContactsViewModel
{
    public const string FILTER_ALL = 'all';

    public const string FILTER_FAVOURITES = 'favourites';

    public const string FILTER_ARCHIVED = 'archived';

    public const string SORT_NAME = 'name';

    public const string SORT_NICKNAME = 'nickname';

    public const string SORT_LAST_SPOKE = 'last-spoke';

    public const string SORT_ADDED = 'added';

    public const string DIRECTION_ASC = 'asc';

    public const string DIRECTION_DESC = 'desc';

    /**
     * How many people a page holds. The next page is appended in place rather
     * than sent to as a second screen, so the list reads as one scroll however
     * many pages it took.
     */
    private const int PER_PAGE = 50;

    /**
     * The column each sort writes into the order by, kept apart from the key
     * the URL carries so the address does not name the schema.
     */
    private const array SORT_COLUMNS = [
        self::SORT_NAME => 'sort_name',
        self::SORT_NICKNAME => 'nickname',
        self::SORT_LAST_SPOKE => 'last_activity_at',
        self::SORT_ADDED => 'created_at',
    ];

    /**
     * The sorts whose column a contact is allowed to have nothing in. They are
     * the ones that need saying where the unknowns go.
     *
     * @var list<string>
     */
    private const array SORTS_THAT_CAN_BE_UNKNOWN = [
        self::SORT_NICKNAME,
        self::SORT_LAST_SPOKE,
    ];

    /** @var Paginator<int, Contact>|null */
    private ?Paginator $contacts = null;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly string $query = '',
        private readonly string $filter = self::FILTER_ALL,
        private readonly string $sort = self::SORT_NAME,
        private readonly string $direction = self::DIRECTION_ASC,
    ) {}

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
        return __('Contacts');
    }

    /**
     * The rows of the table, each one already written the way it is read: the
     * view model formats the dates and settles what an unknown value looks
     * like, so the Blade has nothing to decide.
     *
     * @return array<int, array{name: string, isFavourite: bool, nickname: string, lastSpoke: string, template: string, added: string}>
     */
    public function rows(): array
    {
        return array_map(fn (Contact $contact): array => [
            'name' => $contact->name,
            'isFavourite' => $contact->is_favourite,
            'nickname' => $contact->nickname ?? '—',
            'lastSpoke' => $contact->last_activity_at?->isoFormat('ll') ?? '—',
            'template' => $contact->templateVersion->template->name,
            'added' => $contact->created_at->isoFormat('ll'),
        ], $this->contacts()->items());
    }

    /**
     * The columns of the table, each knowing where its header points and which
     * way the arrow beside it faces.
     *
     * The name is locked. The product uses it to tell one row from another, and
     * a list of avatars with nothing beside them is not a list of people.
     * Template cannot be sorted on: the name lives two tables away, and a join
     * to order a list by something nobody orders a list by is not worth it.
     *
     * @return array<int, array{key: string, label: string, alignRight: bool, isLocked: bool, isSortable: bool, isSorted: bool, isAscending: bool, url: string, title: string}>
     */
    public function columns(): array
    {
        return array_map(function (array $column): array {
            $isSorted = $column['sort'] === $this->sort();

            return [
                'key' => $column['key'],
                'label' => $column['label'],
                'alignRight' => $column['alignRight'],
                'isLocked' => $column['isLocked'],
                'isSortable' => $column['sort'] !== null,
                'isSorted' => $isSorted,
                'isAscending' => $isSorted && $this->direction() === self::DIRECTION_ASC,
                'url' => $this->url(sort: $column['sort'], direction: $this->directionFor($column['sort'], $isSorted)),
                'title' => $column['sort'] === null
                    ? __('This column cannot be sorted.')
                    : __('Sort by :column', ['column' => mb_strtolower($column['label'])]),
            ];
        }, self::definitions());
    }

    /**
     * The chips that narrow the list. Favourites and archived are both columns
     * of the contact; the labels the design also offered are their own model and
     * are not here yet.
     *
     * @return array<int, array{key: string, label: string, url: string, isCurrent: bool}>
     */
    public function filters(): array
    {
        $labels = [
            self::FILTER_ALL => __('Everyone'),
            self::FILTER_FAVOURITES => __('Favourites'),
            self::FILTER_ARCHIVED => __('Archived'),
        ];

        return array_map(fn (string $key): array => [
            'key' => $key,
            'label' => $labels[$key],
            'url' => $this->url(filter: $key),
            'isCurrent' => $key === $this->filter(),
        ], array_keys($labels));
    }

    /**
     * The line under the title, which says whether anything is being hidden.
     * Somebody who arrives at a narrowed list from a link should not have to
     * count the rows to work that out.
     */
    public function resultLine(): string
    {
        if ($this->isNarrowed()) {
            return __(':count of :total shown.', ['count' => $this->matchingCount(), 'total' => $this->total()]);
        }

        return $this->total() === 1
            ? __('One person in this vault.')
            : __('All :count people in this vault, on one scrolling list.', ['count' => $this->total()]);
    }

    /**
     * What the list is ordered by, said in words. The arrows in the header say
     * it too, and this is the version somebody reads rather than decodes.
     */
    public function sortLine(): string
    {
        $label = mb_strtolower($this->sortedColumnLabel());

        return $this->direction() === self::DIRECTION_ASC
            ? __('Sorted by :column, ascending', ['column' => $label])
            : __('Sorted by :column, descending', ['column' => $label]);
    }

    /**
     * The line at the foot, which says the scrolling is over rather than
     * leaving somebody wondering whether more is loading.
     */
    public function endLine(): string
    {
        return $this->matchingCount() === 1
            ? __('End of the list. One person, no pages.')
            : __('End of the list. :count people, no pages.', ['count' => $this->matchingCount()]);
    }

    /**
     * Whether the vault holds nobody to show at all, which is a different screen
     * from a search that matched nobody: one asks somebody to write their first
     * person down, the other asks them to type less.
     */
    public function vaultIsEmpty(): bool
    {
        return ! $this->isNarrowed() && $this->matchingCount() === 0;
    }

    public function noResults(): bool
    {
        return $this->isNarrowed() && $this->matchingCount() === 0;
    }

    /**
     * What the empty screen says, which is not the same sentence twice.
     *
     * A vault whose only people are partial ones is not an empty vault, and
     * saying it holds nobody while the badge beside it counts them is the kind
     * of contradiction somebody reads as a fault in the product. It says where
     * they went instead.
     */
    public function emptyLine(): string
    {
        if ($this->waitingCount() === 0) {
            return __('This vault holds nobody so far. Everybody you write down here shows up on this list, and this is where you come looking for them.');
        }

        return $this->waitingCount() === 1
            ? __('One person here was written down as a partial contact and stays off this list until they are more than a first name.')
            : __(':count people here were written down as partial contacts and stay off this list until they are more than a first name.', ['count' => $this->waitingCount()]);
    }

    /**
     * How many people the vault holds that no chip on this screen can find: the
     * partial ones, who are waiting to be filled in.
     */
    private function waitingCount(): int
    {
        return once(fn (): int => Contact::query()
            ->where('vault_id', $this->vault->id)
            ->where('is_partial', true)
            ->count());
    }

    public function hasMorePages(): bool
    {
        return $this->contacts()->hasMorePages();
    }

    public function nextPageUrl(): ?string
    {
        return $this->contacts()->nextPageUrl();
    }

    public function query(): string
    {
        return $this->query;
    }

    public function filter(): string
    {
        return in_array($this->filter, [self::FILTER_ALL, self::FILTER_FAVOURITES, self::FILTER_ARCHIVED], true)
            ? $this->filter
            : self::FILTER_ALL;
    }

    public function sort(): string
    {
        return array_key_exists($this->sort, self::SORT_COLUMNS)
            ? $this->sort
            : self::SORT_NAME;
    }

    public function direction(): string
    {
        return $this->direction === self::DIRECTION_DESC
            ? self::DIRECTION_DESC
            : self::DIRECTION_ASC;
    }

    public function newContactUrl(): string
    {
        return route('contacts.new', $this->vault);
    }

    public function vaultUrl(): string
    {
        return route('vaults.show', $this->vault);
    }

    /**
     * The vaults the switcher in the sidebar offers, the one being looked at
     * marked rather than offered as a link to where you already are.
     *
     * @return array<int, array{name: string, colour: string, url: string, current: bool}>
     */
    public function vaults(): array
    {
        return Vault::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->get()
            ->map(fn (Vault $vault): array => [
                'name' => $vault->name,
                'colour' => $vault->colour,
                'url' => route('vaults.show', $vault),
                'current' => $vault->is($this->vault),
            ])
            ->all();
    }

    /**
     * The people this page shows.
     *
     * A partial contact is somebody mentioned in a relationship and reduced to a
     * first name, and is out of every one of these lists until they are filled
     * in. Archived contacts are out of all of them but their own chip, which is
     * the only place they can be found again.
     *
     * The order always ends on the primary key. Two people added in the same
     * second, or two people who have never been spoken to, would otherwise land
     * in whatever order the database felt like, and a row can appear on two
     * pages or on none.
     *
     * A column that can be unknown is sorted on twice, the unknowns pushed to
     * the end before the column itself is read. Where they land is otherwise the
     * database's opinion rather than ours: Postgres ranks null above everything
     * and MySQL and SQLite rank it below, so the same list reads differently on
     * two installations. Pushing them to the end says it once, and it is the
     * more useful end: sorting by nickname to be shown a screen of dashes is not
     * what anybody clicked the column for.
     *
     * @return Paginator<int, Contact>
     */
    private function contacts(): Paginator
    {
        $column = self::SORT_COLUMNS[$this->sort()];

        return $this->contacts ??= $this->matching()
            ->with('templateVersion.template')
            ->when(
                in_array($this->sort(), self::SORTS_THAT_CAN_BE_UNKNOWN, true),
                fn (Builder $query): Builder => $query->orderByRaw("{$column} is null"),
            )
            ->orderBy($column, $this->direction())
            ->orderBy('id')
            ->simplePaginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * The people the chip and the search box between them leave, in no
     * particular order: this is what the page is drawn from and what the
     * sentences count, so it is asked for twice and built once.
     *
     * @return Builder<Contact>
     */
    private function matching(): Builder
    {
        return Contact::query()
            ->where('vault_id', $this->vault->id)
            ->where('is_partial', false)
            ->where('is_archived', $this->filter() === self::FILTER_ARCHIVED)
            ->when($this->filter() === self::FILTER_FAVOURITES, fn (Builder $query): Builder => $query->where('is_favourite', true))
            ->when($this->searchTerm() !== '', fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                $query->whereLike('sort_name', "%{$this->searchTerm()}%", caseSensitive: false)
                    ->orWhereLike('nickname', "%{$this->searchTerm()}%", caseSensitive: false);
            }));
    }

    /**
     * What to match a name against, lowercased, and matched case insensitively
     * on top of that. The two are not the same guard and neither covers the
     * other.
     *
     * Lowercasing is what meets `sort_name`, which is written lowercased so the
     * order never depends on the collation underneath. It also folds accented
     * letters, which SQLite's `like` does not: only ASCII is case insensitive
     * there.
     *
     * Asking for the match case insensitively is what reaches a nickname, which
     * is stored as somebody typed it. Whether `like` minds the difference is a
     * question about the database: Postgres does and needs `ilike`, MySQL and
     * SQLite do not. Saying what is meant leaves that answer to the driver.
     */
    private function searchTerm(): string
    {
        return mb_strtolower(mb_trim($this->query));
    }

    /**
     * The columns of the table as they are declared: what each one is called,
     * which sort it answers to, and how it is drawn. The header, the picker and
     * the sentence saying what the list is ordered by all read this, so a column
     * is described once.
     *
     * @return array<int, array{key: string, label: string, sort: string|null, alignRight: bool, isLocked: bool}>
     */
    private static function definitions(): array
    {
        return [
            ['key' => 'name', 'label' => __('Name'), 'sort' => self::SORT_NAME, 'alignRight' => false, 'isLocked' => true],
            ['key' => 'nickname', 'label' => __('Nickname'), 'sort' => self::SORT_NICKNAME, 'alignRight' => false, 'isLocked' => false],
            ['key' => 'last-spoke', 'label' => __('Last spoke'), 'sort' => self::SORT_LAST_SPOKE, 'alignRight' => true, 'isLocked' => false],
            ['key' => 'template', 'label' => __('Template'), 'sort' => null, 'alignRight' => false, 'isLocked' => false],
            ['key' => 'added', 'label' => __('Added'), 'sort' => self::SORT_ADDED, 'alignRight' => true, 'isLocked' => false],
        ];
    }

    /**
     * What the column being sorted on is called. There is always one, since the
     * sort falls back to the name.
     */
    private function sortedColumnLabel(): string
    {
        foreach (self::definitions() as $column) {
            if ($column['sort'] === $this->sort()) {
                return $column['label'];
            }
        }

        return __('Name');
    }

    /**
     * How many people this list would show with nothing narrowing it, which is
     * what the line under the title counts against.
     */
    private function total(): int
    {
        return once(fn (): int => $this->vault->contacts()->listed()->count());
    }

    /**
     * How many people the list holds altogether, rather than how many of them
     * this page rendered: the sentence at the foot is about the list, and a page
     * of it is an implementation detail of the scrolling.
     */
    private function matchingCount(): int
    {
        return once(fn (): int => $this->matching()->count());
    }

    /**
     * Whether the list is showing less than the vault holds, in which case the
     * line under the title says so.
     */
    private function isNarrowed(): bool
    {
        return $this->searchTerm() !== '' || $this->filter() !== self::FILTER_ALL;
    }

    /**
     * Which way a header sends the list. Clicking the column already sorted on
     * turns it around; clicking another one starts it off the way that column
     * is usually read, which for a date is the most recent first.
     */
    private function directionFor(?string $sort, bool $isSorted): string
    {
        if ($isSorted) {
            return $this->direction() === self::DIRECTION_ASC ? self::DIRECTION_DESC : self::DIRECTION_ASC;
        }

        return in_array($sort, [self::SORT_LAST_SPOKE, self::SORT_ADDED], true)
            ? self::DIRECTION_DESC
            : self::DIRECTION_ASC;
    }

    /**
     * This screen again, with one thing about it changed and everything else
     * left where it was: narrowing a list must not quietly undo the sort.
     *
     * The filter is a segment of the address and the rest is a query string, so
     * everybody is the path with nothing after it rather than a word saying so.
     */
    private function url(?string $sort = null, ?string $direction = null, ?string $filter = null): string
    {
        return route('contacts.index', [
            'vault' => $this->vault,
            'filter' => $this->segmentFor($filter ?? $this->filter()),
            'query' => $this->query === '' ? null : $this->query,
            'sort' => $sort ?? $this->sort(),
            'direction' => $direction ?? $this->direction(),
        ]);
    }

    /**
     * Where the form that searches this list posts to, which is the path of the
     * chip in force and nothing else: a browser throws away the query string of
     * a GET action and puts its own fields there instead.
     */
    public function searchUrl(): string
    {
        return route('contacts.index', [
            'vault' => $this->vault,
            'filter' => $this->segmentFor($this->filter()),
        ]);
    }

    private function segmentFor(string $filter): ?string
    {
        return $filter === self::FILTER_ALL ? null : $filter;
    }
}
