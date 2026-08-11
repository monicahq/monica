<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;

/**
 * What the people screen shows: everybody in one vault, on one list, narrowed by
 * what was typed and by which chip is in force, in the order asked for.
 *
 * The list is rows rather than a table. What a contact carries that is worth
 * putting beside their name is their nickname, the template their page renders
 * from and the last time somebody wrote something down about them, and that is
 * three things: a table of three columns is a list with lines drawn on it. How
 * you know somebody, their birthday and their address are each their own model
 * and none of them exists yet.
 *
 * Last spoke is here although nothing writes `last_activity_at` yet, because
 * activities are a later model. It is what somebody orders this list by, and it
 * comes to life on its own the day activities land.
 *
 * In name order the rows are grouped under the letter they begin with, which is
 * what makes a long list something to be scanned rather than read. In any other
 * order there are no letters: a run of names under "F" that is ordered by when
 * they were added is a heading that lies.
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

    /**
     * How many people each chip would show, asked for once each: the chips
     * carry their counts and the sentences count against the same numbers.
     *
     * @var array<string, int>
     */
    private array $counts = [];

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
        return __('People');
    }

    /**
     * The line under the title. It says what the screen is for and what happens
     * to what you do to it, since somebody who narrows a list and then reloads
     * the page has to know which of the two they are looking at.
     */
    public function description(): string
    {
        return __('Everybody you keep in :vault. Narrowing the list and ordering it both stay in the address, so it comes back the way you left it.', [
            'vault' => $this->vault->name,
        ]);
    }

    /**
     * The number beside "People" in the bar: how many the plain list holds, not
     * how many the chip in force leaves. It says how big the vault is and must
     * not move every time somebody types in the search box.
     */
    public function headcount(): int
    {
        return $this->countOf(self::FILTER_ALL);
    }

    /**
     * The rows of the list, in runs under the letter they begin with.
     *
     * A run whose letter is null is drawn without a heading. That happens on the
     * second page of a list that broke mid-letter, where a heading would read as
     * a second run of the same letter rather than the rest of the first, and on
     * every page of a list ordered by something a letter says nothing about.
     *
     * The date at the end of a row is the last time somebody wrote something
     * down about that person, and when nobody ever has, the day they were
     * written down themselves. Nothing fills `last_activity_at` until activities
     * land, so the first version of that row would otherwise be a column of the
     * word "never" repeated down the screen. The caption says which of the two
     * it is.
     *
     * @return array<int, array{letter: string|null, people: array<int, array{name: string, isFavourite: bool, isArchived: bool, nickname: string|null, template: string, when: string, whenLabel: string}>}>
     */
    public function groups(): array
    {
        $groups = [];
        $letter = $this->isGrouped() ? $this->letterBefore() : null;

        foreach ($this->contacts()->items() as $contact) {
            $current = $this->isGrouped() ? $this->letterOf($contact) : null;

            if ($groups === [] || $current !== $letter) {
                $groups[] = ['letter' => $current === $letter ? null : $current, 'people' => []];
                $letter = $current;
            }

            $groups[array_key_last($groups)]['people'][] = [
                'name' => $contact->name,
                'isFavourite' => $contact->is_favourite,
                'isArchived' => $contact->is_archived,
                'nickname' => $contact->nickname,
                'template' => $contact->templateVersion->template->name,
                'when' => ($contact->last_activity_at ?? $contact->created_at)->isoFormat('ll'),
                'whenLabel' => $contact->last_activity_at === null ? __('added') : __('last spoke'),
            ];
        }

        return $groups;
    }

    /**
     * The chips that narrow the list, each carrying how many people it would
     * leave. The counts ignore what is in the search box: a chip says how many
     * people of that kind the vault holds, and a number that moved with every
     * letter typed would be counting something else.
     *
     * A chip with nobody behind it carries no number. Nothing is waiting there,
     * and a row of zeroes is noise on the way to the one chip that has people
     * under it.
     *
     * Favourites and archived are both columns of the contact; the labels the
     * design also offered are their own model and are not here yet.
     *
     * @return array<int, array{key: string, label: string, icon: string, count: int|null, url: string, isCurrent: bool}>
     */
    public function filters(): array
    {
        $chips = [
            self::FILTER_ALL => ['label' => __('Everyone'), 'icon' => 'people'],
            self::FILTER_FAVOURITES => ['label' => __('Favourites'), 'icon' => 'star'],
            self::FILTER_ARCHIVED => ['label' => __('Archived'), 'icon' => 'file'],
        ];

        return array_map(fn (string $key): array => [
            'key' => $key,
            'label' => $chips[$key]['label'],
            'icon' => $chips[$key]['icon'],
            'count' => $this->countOf($key) === 0 ? null : $this->countOf($key),
            'url' => $this->url(filter: $key),
            'isCurrent' => $key === $this->filter(),
        ], array_keys($chips));
    }

    /**
     * What the list can be ordered by.
     *
     * @return array<int, array{value: string, label: string, isCurrent: bool}>
     */
    public function sorts(): array
    {
        return array_map(fn (array $sort): array => [
            'value' => $sort['key'],
            'label' => $sort['label'],
            'isCurrent' => $sort['key'] === $this->sort(),
        ], self::definitions());
    }

    /**
     * Which way round that order runs, said in the words of the column it
     * applies to: a list of names goes from A to Z and a list of dates goes from
     * the newest, and "ascending" is a word about a database rather than about
     * either of them.
     *
     * @return array<int, array{value: string, label: string, isCurrent: bool}>
     */
    public function directions(): array
    {
        $labels = in_array($this->sort(), [self::SORT_LAST_SPOKE, self::SORT_ADDED], true)
            ? [self::DIRECTION_DESC => __('Newest first'), self::DIRECTION_ASC => __('Oldest first')]
            : [self::DIRECTION_ASC => __('A to Z'), self::DIRECTION_DESC => __('Z to A')];

        return array_map(fn (string $key): array => [
            'value' => $key,
            'label' => $labels[$key],
            'isCurrent' => $key === $this->direction(),
        ], array_keys($labels));
    }

    /**
     * The line beside the search box, which says whether anything is being
     * hidden. Somebody who arrives at a narrowed list from a link should not
     * have to count the rows to work that out.
     */
    public function resultLine(): string
    {
        if ($this->isNarrowed()) {
            return __(':count of :total shown.', ['count' => $this->matchingCount(), 'total' => $this->headcount()]);
        }

        return $this->headcount() === 1
            ? __('One person in this vault.')
            : __('All :count people in this vault, on one scrolling list.', ['count' => $this->headcount()]);
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
     * The people this page shows.
     *
     * A partial contact is somebody mentioned in a relationship and reduced to a
     * first name, and is out of every one of these lists until they are filled
     * in. Archived contacts are out of all of them but their own chip, which is
     * the only place they can be found again.
     *
     * @return Paginator<int, Contact>
     */
    private function contacts(): Paginator
    {
        return $this->contacts ??= $this->ordered()
            ->with('templateVersion.template')
            ->simplePaginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * The people the chip and the search box leave, in the order asked for.
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
     * what anybody asked the list for.
     *
     * @return Builder<Contact>
     */
    private function ordered(): Builder
    {
        $column = self::SORT_COLUMNS[$this->sort()];

        return $this->matching()
            ->when(
                in_array($this->sort(), self::SORTS_THAT_CAN_BE_UNKNOWN, true),
                fn (Builder $query): Builder => $query->orderByRaw("{$column} is null"),
            )
            ->orderBy($column, $this->direction())
            ->orderBy('id');
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
     * The orders the list can be put in: what each one is called and which
     * column it reads. The select and the sentence beside it both read this, so
     * an order is described once.
     *
     * The template is not among them. Its name lives two tables away, and a join
     * to order a list by something nobody orders a list by is not worth it.
     *
     * @return array<int, array{key: string, label: string}>
     */
    private static function definitions(): array
    {
        return [
            ['key' => self::SORT_NAME, 'label' => __('Name')],
            ['key' => self::SORT_NICKNAME, 'label' => __('Nickname')],
            ['key' => self::SORT_LAST_SPOKE, 'label' => __('Last spoke')],
            ['key' => self::SORT_ADDED, 'label' => __('Added')],
        ];
    }

    /**
     * Whether the rows are drawn in runs under a letter, which only the name
     * order earns: a letter over a list ordered by anything else is a heading
     * that has nothing to do with what is under it.
     */
    private function isGrouped(): bool
    {
        return $this->sort() === self::SORT_NAME;
    }

    private function letterOf(Contact $contact): string
    {
        return mb_strtoupper(mb_substr($contact->sort_name, 0, 1)) ?: '#';
    }

    /**
     * The letter the page before this one ended on, so a run that was cut in
     * half by the pagination is drawn as the rest of itself rather than as a
     * second run under the same heading.
     *
     * It costs one row of one query, and only from the second page on. The
     * alternative was carrying the letter in the address, which would put a
     * detail of how the list is drawn into something somebody can bookmark.
     */
    private function letterBefore(): ?string
    {
        if ($this->contacts()->currentPage() === 1) {
            return null;
        }

        $contact = $this->ordered()
            ->skip(($this->contacts()->currentPage() - 1) * self::PER_PAGE - 1)
            ->first();

        return $contact === null ? null : $this->letterOf($contact);
    }

    /**
     * How many people one chip would show. The archived chip is the only one
     * that looks at the archived people, which is what keeps them out of the
     * other two counts.
     */
    private function countOf(string $filter): int
    {
        return $this->counts[$filter] ??= Contact::query()
            ->where('vault_id', $this->vault->id)
            ->where('is_partial', false)
            ->where('is_archived', $filter === self::FILTER_ARCHIVED)
            ->when($filter === self::FILTER_FAVOURITES, fn (Builder $query): Builder => $query->where('is_favourite', true))
            ->count();
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
     * line beside the search box says so.
     */
    private function isNarrowed(): bool
    {
        return $this->searchTerm() !== '' || $this->filter() !== self::FILTER_ALL;
    }

    /**
     * This screen again, with one thing about it changed and everything else
     * left where it was: narrowing a list must not quietly undo the sort.
     *
     * The filter is a segment of the address and the rest is a query string, so
     * everybody is the path with nothing after it rather than a word saying so.
     */
    private function url(?string $filter = null): string
    {
        return route('contacts.index', [
            'vault' => $this->vault,
            'filter' => $this->segmentFor($filter ?? $this->filter()),
            'query' => $this->query === '' ? null : $this->query,
            'sort' => $this->sort(),
            'direction' => $this->direction(),
        ]);
    }

    /**
     * Where the two forms on this screen post to, which is the path of the chip
     * in force and nothing else: a browser throws away the query string of a GET
     * action and puts its own fields there instead.
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
