<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ContactGroupingEnum;
use App\Enums\ContactSortEnum;
use App\Helpers\LocaleCollation;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;

class ContactsViewModel
{
    public const string DIRECTION_ASC = 'asc';

    public const string DIRECTION_DESC = 'desc';

    private const int PER_PAGE = 50;

    /** @var Paginator<int, Contact>|null */
    private ?Paginator $contacts = null;

    /** @var array<string, int> */
    private array $counts = [];

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly string $query = '',
        private readonly string $filter = ContactGroupingEnum::Everyone->value,
        private readonly string $sort = ContactSortEnum::Name->value,
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

    public function headcount(): int
    {
        return $this->countOf(ContactGroupingEnum::Everyone);
    }

    /**
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
     * @return array<int, array{key: string, label: string, icon: string, count: int|null, url: string, isCurrent: bool}>
     */
    public function filters(): array
    {
        return array_map(fn (ContactGroupingEnum $grouping): array => [
            'key' => $grouping->value,
            'label' => __($grouping->label()),
            'icon' => $grouping->icon(),
            'count' => $this->countOf($grouping) === 0 ? null : $this->countOf($grouping),
            'url' => $this->url(filter: $grouping->value),
            'isCurrent' => $grouping === $this->grouping(),
        ], ContactGroupingEnum::cases());
    }

    /** @return array<int, array{value: string, label: string, isCurrent: bool}> */
    public function sorts(): array
    {
        return array_map(fn (array $sort): array => [
            'value' => $sort['key'],
            'label' => $sort['label'],
            'isCurrent' => $sort['key'] === $this->sort(),
        ], self::definitions());
    }

    /** @return array<int, array{value: string, label: string, isCurrent: bool}> */
    public function directions(): array
    {
        $labels = $this->sortOrder()->isChronological()
            ? [self::DIRECTION_DESC => __('Newest first'), self::DIRECTION_ASC => __('Oldest first')]
            : [self::DIRECTION_ASC => __('A to Z'), self::DIRECTION_DESC => __('Z to A')];

        return array_map(fn (string $key): array => [
            'value' => $key,
            'label' => $labels[$key],
            'isCurrent' => $key === $this->direction(),
        ], array_keys($labels));
    }

    public function resultLine(): string
    {
        if ($this->isNarrowed()) {
            return trans_choice(
                ':count of :total shown.|:count of :total shown.',
                $this->matchingCount(),
                ['total' => $this->headcount()],
            );
        }

        if ($this->headcount() === 0) {
            return '';
        }

        return trans_choice('{1}One person in this vault.|All :count people in this vault, on a single list.', $this->headcount());
    }

    public function endLine(): string
    {
        if ($this->matchingCount() === 0) {
            return '';
        }

        return trans_choice('{1}End of the list. One person, no pages.|End of the list. :count people, no pages.', $this->matchingCount());
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

        return trans_choice('{1}One person here was written down as a partial contact and stays off this list until they are more than a first name.|:count people here were written down as partial contacts and stay off this list until they are more than a first name.', $this->waitingCount());
    }

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
        return $this->grouping()->value;
    }

    public function sort(): string
    {
        return $this->sortOrder()->value;
    }

    private function grouping(): ContactGroupingEnum
    {
        return ContactGroupingEnum::fromValueOrEveryone($this->filter);
    }

    private function sortOrder(): ContactSortEnum
    {
        return ContactSortEnum::fromValueOrName($this->sort);
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

    /** @return Paginator<int, Contact> */
    private function contacts(): Paginator
    {
        return $this->contacts ??= $this->ordered()
            ->with(['templateVersion.template', 'avatar'])
            ->simplePaginate(self::PER_PAGE)
            ->withQueryString();
    }

    /** @return Builder<Contact> */
    private function ordered(): Builder
    {
        return $this->matching()->sortedBy($this->sortOrder(), $this->direction());
    }

    /** @return Builder<Contact> */
    private function matching(): Builder
    {
        return Contact::query()
            ->where('vault_id', $this->vault->id)
            ->inGrouping($this->grouping())
            ->matchingSearch($this->query);
    }

    private function searchTerm(): string
    {
        return mb_trim($this->query);
    }

    /** @return array<int, array{key: string, label: string}> */
    private static function definitions(): array
    {
        return [
            ['key' => ContactSortEnum::Name->value, 'label' => __('Name')],
            ['key' => ContactSortEnum::Nickname->value, 'label' => __('Nickname')],
            ['key' => ContactSortEnum::LastSpoke->value, 'label' => __('Last spoke')],
            ['key' => ContactSortEnum::Added->value, 'label' => __('Added')],
        ];
    }

    private function isGrouped(): bool
    {
        return $this->sortOrder() === ContactSortEnum::Name;
    }

    private function letterOf(Contact $contact): string
    {
        return LocaleCollation::headingLetter($contact->sort_name);
    }

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

    private function countOf(ContactGroupingEnum $grouping): int
    {
        return $this->counts[$grouping->value] ??= Contact::query()
            ->where('vault_id', $this->vault->id)
            ->inGrouping($grouping)
            ->count();
    }

    private function matchingCount(): int
    {
        return once(fn (): int => $this->matching()->count());
    }

    private function isNarrowed(): bool
    {
        return $this->searchTerm() !== '' || $this->grouping() !== ContactGroupingEnum::Everyone;
    }

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

    public function searchUrl(): string
    {
        return route('contacts.index', [
            'vault' => $this->vault,
            'filter' => $this->segmentFor($this->filter()),
        ]);
    }

    private function segmentFor(string $filter): ?string
    {
        return $filter === ContactGroupingEnum::Everyone->value ? null : $filter;
    }
}
