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
 * In name order the rows are grouped under the letter they begin with, which is
 * what makes a long list something to be scanned rather than read. In any other
 * order there are no letters: a run of names under "F" ordered by when they were
 * added is a heading that lies.
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

    private const int PER_PAGE = 50;

    /**
     * Kept apart from the key the URL carries, so the address does not name the
     * schema.
     */
    private const array SORT_COLUMNS = [
        self::SORT_NAME => 'sort_name',
        self::SORT_NICKNAME => 'nickname',
        self::SORT_LAST_SPOKE => 'last_activity_at',
        self::SORT_ADDED => 'created_at',
    ];

    /**
     * The sorts whose column a contact may have nothing in, and which therefore
     * need saying where the unknowns go.
     *
     * @var list<string>
     */
    private const array SORTS_THAT_CAN_BE_UNKNOWN = [
        self::SORT_NICKNAME,
        self::SORT_LAST_SPOKE,
    ];

    /** @var Paginator<int, Contact>|null */
    private ?Paginator $contacts = null;

    /** @var array<string, int> */
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

    public function description(): string
    {
        return __('Everybody you keep in :vault. Narrowing the list and ordering it both stay in the address, so it comes back the way you left it.', [
            'vault' => $this->vault->name,
        ]);
    }

    /**
     * How many the plain list holds, not how many the chip in force leaves: it
     * says how big the vault is, so it must not move as somebody types.
     */
    public function headcount(): int
    {
        return $this->countOf(self::FILTER_ALL);
    }

    /**
     * The rows of the list, in runs under the letter they begin with. A run
     * whose letter is null is drawn without a heading: either the list is
     * ordered by something a letter says nothing about, or the page broke
     * mid-letter and this run is the rest of the previous page's.
     *
     * @return array<int, array{letter: string|null, people: array<int, array{name: string, isFavourite: bool, isArchived: bool, nickname: string|null, avatarUrl: string|null, template: string, when: string, whenLabel: string, url: string}>}>
     */
    public function groups(): array
    {
        $carriedOver = $this->isGrouped() ? $this->letterBefore() : null;
        $groups = [];
        $openLetter = null;

        foreach ($this->contacts()->items() as $contact) {
            $letter = $this->isGrouped() ? $this->letterOf($contact) : null;

            if ($groups === [] || $letter !== $openLetter) {
                $continuesThePreviousPage = $groups === [] && $letter === $carriedOver;

                $groups[] = ['letter' => $continuesThePreviousPage ? null : $letter, 'people' => []];
                $openLetter = $letter;
            }

            $groups[array_key_last($groups)]['people'][] = $this->rowFor($contact);
        }

        return $groups;
    }

    /**
     * The date at the end of a row is the last time somebody wrote something
     * down about that person, and the day they were written down themselves
     * when nobody ever has. Nothing fills `last_activity_at` until activities
     * land, so the caption says which of the two it is.
     *
     * @return array{name: string, isFavourite: bool, isArchived: bool, nickname: string|null, avatarUrl: string|null, template: string, when: string, whenLabel: string, url: string}
     */
    private function rowFor(Contact $contact): array
    {
        return [
            'name' => $contact->name,
            'isFavourite' => $contact->is_favourite,
            'isArchived' => $contact->is_archived,
            'nickname' => $contact->nickname,
            'avatarUrl' => $contact->avatar === null
                ? null
                : route('files.show', ['vault' => $this->vault, 'file' => $contact->avatar]),
            'template' => $contact->templateVersion->template->name,
            'when' => ($contact->last_activity_at ?? $contact->created_at)->isoFormat('ll'),
            'whenLabel' => $contact->last_activity_at === null ? __('added') : __('last spoke'),
            'url' => route('contacts.show', ['vault' => $this->vault, 'contact' => $contact]),
        ];
    }

    /**
     * The chips that narrow the list. The counts ignore the search box: a chip
     * says how many people of that kind the vault holds, and a number moving
     * with every letter typed would be counting something else. A chip with
     * nobody behind it carries no number rather than a zero.
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
     * Said in the words of the column it applies to: "ascending" is a word about
     * a database rather than about a list of names or a list of dates.
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
     * Whether anything is being hidden. Somebody arriving at a narrowed list
     * from a link should not have to count the rows to work that out.
     */
    public function resultLine(): string
    {
        if ($this->isNarrowed()) {
            return trans_choice(
                ':count of :total shown.|:count of :total shown.',
                $this->matchingCount(),
                ['total' => $this->headcount()],
            );
        }

        // The wording pinned to one names one person, so it cannot stand in for
        // none. An empty vault says so in the panel below instead.
        if ($this->headcount() === 0) {
            return '';
        }

        return trans_choice('{1}One person in this vault.|All :count people in this vault, on one scrolling list.', $this->headcount());
    }

    /**
     * Says the scrolling is over, rather than leaving somebody wondering
     * whether more is loading.
     */
    public function endLine(): string
    {
        // There is no end of a list that never started, and the wording pinned
        // to one cannot serve zero in any language. The screen shows its own
        // sentence when nothing matched, so this one says nothing there.
        if ($this->matchingCount() === 0) {
            return '';
        }

        return trans_choice('{1}End of the list. One person, no pages.|End of the list. :count people, no pages.', $this->matchingCount());
    }

    /**
     * A different screen from a search that matched nobody: one asks somebody
     * to write their first person down, the other asks them to type less.
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
     * A vault whose only people are partial ones is not an empty vault: saying
     * it holds nobody while the badge beside it counts them reads as a fault in
     * the product, so this says where they went instead.
     */
    public function emptyLine(): string
    {
        if ($this->waitingCount() === 0) {
            return __('This vault holds nobody so far. Everybody you write down here shows up on this list, and this is where you come looking for them.');
        }

        return trans_choice('{1}One person here was written down as a partial contact and stays off this list until they are more than a first name.|:count people here were written down as partial contacts and stay off this list until they are more than a first name.', $this->waitingCount());
    }

    /**
     * The partial ones, who no chip on this screen can find.
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
     * @return Paginator<int, Contact>
     */
    private function contacts(): Paginator
    {
        return $this->contacts ??= $this->ordered()
            ->with(['templateVersion.template', 'avatar'])
            ->simplePaginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * The order always ends on the primary key, or two people added in the same
     * second land in whatever order the database felt like and a row appears on
     * two pages or on none.
     *
     * A column that can be unknown is sorted on twice, the unknowns pushed to
     * the end first. Postgres ranks null above everything and MySQL and SQLite
     * rank it below, so saying it here is what keeps the list reading the same
     * on every installation.
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
     * The people the chip and the search box between them leave. A partial
     * contact is out of every one of these lists until they are filled in, and
     * archived contacts are out of all but their own chip.
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
     * Lowercased here and matched case insensitively on top of that. Neither
     * guard covers the other: lowercasing is what meets `sort_name`, which is
     * stored lowercased and which SQLite's `like` would not fold the accents
     * of, and the case insensitive match is what reaches a nickname, stored as
     * somebody typed it.
     */
    private function searchTerm(): string
    {
        return mb_strtolower(mb_trim($this->query));
    }

    /**
     * The template is not among them: its name lives two tables away, and a
     * join to order a list by something nobody orders a list by is not worth it.
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
     * Only the name order earns letters: one over a list ordered by anything
     * else is a heading with nothing to do with what is under it.
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
     * The letter the page before this one ended on, so a run cut in half by the
     * pagination is drawn as the rest of itself. It costs one row of one query
     * from the second page on; the alternative was putting a detail of how the
     * list is drawn into something somebody can bookmark.
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
     * The archived chip is the only one that looks at the archived people,
     * which is what keeps them out of the other two counts.
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
     * How many the list holds altogether, rather than how many this page
     * rendered: a page is an implementation detail of the scrolling.
     */
    private function matchingCount(): int
    {
        return once(fn (): int => $this->matching()->count());
    }

    private function isNarrowed(): bool
    {
        return $this->searchTerm() !== '' || $this->filter() !== self::FILTER_ALL;
    }

    /**
     * This screen again, with one thing changed and everything else left where
     * it was: narrowing a list must not quietly undo the sort.
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
     * The path of the chip in force and nothing else: a browser throws away the
     * query string of a GET action and puts its own fields there instead.
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
