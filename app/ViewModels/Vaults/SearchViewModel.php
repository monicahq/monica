<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Actions\SearchRecords;
use App\Enums\DateFormatEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\SearchWords;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\LifeEvent;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\HtmlString;

class SearchViewModel
{
    public const string IDLE = 'idle';

    /** @var array{status: string, words: list<string>, groups: list<array{key: string, label: string, results: LengthAwarePaginator<int, Contact|Entity|LifeEvent>}>}|null */
    private ?array $result = null;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly string $query,
        private readonly bool $acrossAccount = false,
        private readonly ?string $group = null,
        private readonly int $page = 1,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function title(): string
    {
        return __('Search');
    }

    public function query(): string
    {
        return $this->query;
    }

    public function acrossAccount(): bool
    {
        return $this->acrossAccount;
    }

    public function formUrl(): string
    {
        return route('search.index', ['vault' => $this->vault]);
    }

    public function status(): string
    {
        if (mb_trim($this->query) === '') {
            return self::IDLE;
        }

        return $this->result()['status'];
    }

    public function resultCount(): int
    {
        return (int) collect($this->result()['groups'])->sum(fn (array $group): int => $group['results']->total());
    }

    public function countLine(): string
    {
        $query = mb_trim($this->query);

        return $this->acrossAccount
            ? trans_choice(':count result for “:query” in every vault|:count results for “:query” in every vault', $this->resultCount(), ['query' => $query])
            : trans_choice(':count result for “:query” in this vault|:count results for “:query” in this vault', $this->resultCount(), ['query' => $query]);
    }

    public function nothingFoundTitle(): string
    {
        return __('Nothing matches “:query”', ['query' => mb_trim($this->query)]);
    }

    public function nothingFoundHint(): string
    {
        return $this->acrossAccount
            ? __('Try another spelling or a shorter word.')
            : __('Try another spelling, or search every vault.');
    }

    /** @return list<array{key: string, id: string, label: string, total: int, rows: list<array{name: string, markedName: HtmlString, url: ?string, avatarUrl: ?string, isLifeEvent: bool, vault: ?string, isArchived: bool, date: ?string, matchedLabel: ?string, matchedExcerpt: ?HtmlString}>, nextPageUrl: ?string}> */
    public function groups(): array
    {
        if ($this->status() !== SearchRecords::SEARCHED) {
            return [];
        }

        $words = $this->result()['words'];
        $groups = [];

        foreach ($this->result()['groups'] as $group) {
            $results = $group['results'];
            $records = EloquentCollection::make($results->items());

            if (! $records->first() instanceof LifeEvent) {
                $records->load('avatar');
            }

            $groups[] = [
                'key' => $group['key'],
                'id' => "search-group-{$group['key']}",
                'label' => $group['label'],
                'total' => $results->total(),
                'rows' => $records->map(fn (Contact|Entity|LifeEvent $record): array => $this->row($record, $words))->values()->all(),
                'nextPageUrl' => $results->hasMorePages() ? route('search.index', array_filter([
                    'vault' => $this->vault,
                    'query' => $this->query,
                    'scope' => $this->acrossAccount ? 'account' : null,
                    'group' => $group['key'],
                    'page' => $results->currentPage() + 1,
                ])) : null,
            ];
        }

        return $groups;
    }

    /**
     * @param  list<string>  $words
     * @return array{name: string, markedName: HtmlString, url: ?string, avatarUrl: ?string, isLifeEvent: bool, vault: ?string, isArchived: bool, date: ?string, matchedLabel: ?string, matchedExcerpt: ?HtmlString}
     */
    private function row(Contact|Entity|LifeEvent $record, array $words): array
    {
        $matched = $record->matchedField($words, withSensitive: true);

        return [
            'name' => $record->searchableName(),
            'markedName' => $this->marked($record->searchableName(), $words),
            'url' => $this->url($record),
            'avatarUrl' => $record instanceof LifeEvent || $record->avatar === null
                ? null
                : route('files.variants.show', ['vault' => $record->vault, 'file' => $record->avatar, 'size' => Contact::AVATAR_THUMBNAIL_PIXELS]),
            'isLifeEvent' => $record instanceof LifeEvent,
            'vault' => $this->acrossAccount ? $record->vault->name : null,
            'isArchived' => ! $record instanceof LifeEvent && $record->is_archived,
            'date' => $record instanceof LifeEvent ? DateFormatEnum::write($record->happened_on, $this->dateFormat()) : null,
            'matchedLabel' => $matched['label'] ?? null,
            'matchedExcerpt' => isset($matched['excerpt']) ? $this->marked($matched['excerpt'], $words) : null,
        ];
    }

    private function url(Contact|Entity|LifeEvent $record): ?string
    {
        if ($record instanceof Contact) {
            return route('contacts.show', ['vault' => $record->vault, 'contact' => $record]);
        }

        if ($record instanceof Entity) {
            return route('entities.index', ['vault' => $record->vault, 'entityType' => $record->entityType]);
        }

        foreach ($record->participants as $participation) {
            if ($participation->participant instanceof Contact) {
                return route('contacts.show', ['vault' => $record->vault, 'contact' => $participation->participant]);
            }
        }

        return null;
    }

    /**
     * The text with every word the search matched wrapped in a mark, everything else escaped.
     *
     * @param  list<string>  $words
     */
    private function marked(string $text, array $words): HtmlString
    {
        $locale = $this->user->account->locale();
        $pieces = preg_split('/([^\p{L}\p{N}]+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $html = '';

        foreach ($pieces as $piece) {
            $isWord = preg_match('/^[\p{L}\p{N}]+$/u', $piece) === 1;
            $matches = $isWord && collect($words)->contains(fn (string $word): bool => SearchWords::matches($piece, $word, $locale));

            $html .= $matches ? '<mark>'.e($piece).'</mark>' : e($piece);
        }

        return new HtmlString($html);
    }

    private function dateFormat(): string
    {
        return $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];
    }

    /** @return array{status: string, words: list<string>, groups: list<array{key: string, label: string, results: LengthAwarePaginator<int, Contact|Entity|LifeEvent>}>} */
    private function result(): array
    {
        return $this->result ??= new SearchRecords(
            user: $this->user,
            vault: $this->vault,
            query: $this->query,
            acrossAccount: $this->acrossAccount,
            group: $this->group,
            withSensitive: true,
            page: $this->page,
        )->execute();
    }
}
