<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\LifeEvent;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Scout\Builder;
use Throwable;

/**
 * Find the records of a vault, or of every vault of the account, holding every word somebody
 * typed, grouped the way the product lists them: people, each entity type, then life events. The search
 * engine decides what matches and in which order; this action decides where it looks.
 */
class SearchRecords
{
    public const int PER_PAGE = 20;

    public const string SEARCHED = 'searched';

    public const string TOO_SHORT = 'too_short';

    public const string UNAVAILABLE = 'unavailable';

    private const array SUPPORTED_ENGINES = ['algolia', 'meilisearch', 'typesense', 'database'];

    /** @var list<int>|null */
    private ?array $vaultIds = null;

    /** @var list<int>|null */
    private ?array $peopleVaultIds = null;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly string $query,
        private readonly bool $acrossAccount = false,
        private readonly ?string $group = null,
        private readonly bool $withSensitive = true,
        private readonly int $page = 1,
        private readonly int $perPage = self::PER_PAGE,
        private readonly bool $respectsHiddenSections = false,
    ) {}

    /** @return array{status: string, words: list<string>, groups: list<array{key: string, label: string, results: LengthAwarePaginator<int, Contact|Entity|LifeEvent>}>} */
    public function execute(): array
    {
        $this->validate();

        $words = $this->words();

        if (mb_strlen(implode('', $words)) < 2) {
            return ['status' => self::TOO_SHORT, 'words' => $words, 'groups' => []];
        }

        if (! in_array(config('scout.driver'), self::SUPPORTED_ENGINES, true)) {
            return ['status' => self::UNAVAILABLE, 'words' => $words, 'groups' => []];
        }

        try {
            $groups = $this->search();
        } catch (Throwable $e) {
            report($e);

            return ['status' => self::UNAVAILABLE, 'words' => $words, 'groups' => []];
        }

        return ['status' => self::SEARCHED, 'words' => $words, 'groups' => $groups];
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }
    }

    /** @return list<string> */
    private function words(): array
    {
        return preg_split('/[^\p{L}\p{N}]+/u', $this->query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /** @return list<array{key: string, label: string, results: LengthAwarePaginator<int, Contact|Entity|LifeEvent>}> */
    private function search(): array
    {
        $groups = [];

        foreach ($this->candidateGroups() as $key => $candidate) {
            if ($this->group !== null && $this->group !== $key) {
                continue;
            }

            if (! $candidate['records']->exists()) {
                continue;
            }

            $results = $candidate['builder']->paginate($this->perPage, 'page', $this->group === null ? 1 : $this->page);

            if ($results->total() === 0) {
                continue;
            }

            $records = EloquentCollection::make($results->items());
            $records->first()?->makeSearchableUsing($records);
            $records->load('vault');

            $groups[] = ['key' => $key, 'label' => $candidate['label'], 'results' => $results];
        }

        return $groups;
    }

    /**
     * Every group a result can fall in, in the order they are shown. A group holding no record in
     * the scope is not asked for at all: an engine refuses to search an index that has never been
     * sent a document.
     *
     * @return array<string, array{label: string, records: EloquentBuilder<Contact>|EloquentBuilder<Entity>|EloquentBuilder<LifeEvent>, builder: Builder<Contact>|Builder<Entity>|Builder<LifeEvent>}>
     */
    private function candidateGroups(): array
    {
        $groups = ['people' => [
            'label' => __('People'),
            'records' => Contact::query()->where('account_id', $this->user->account_id)->whereIn('vault_id', $this->peopleVaultIds()),
            'builder' => $this->scoped(Contact::search($this->query), $this->peopleVaultIds()),
        ]];

        $entityTypes = EntityType::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->get();

        foreach ($entityTypes as $entityType) {
            $groups["entity-type-{$entityType->slug}"] = [
                'label' => $entityType->name_plural,
                'records' => Entity::query()->where('entity_type_id', $entityType->id)->whereIn('vault_id', $this->peopleVaultIds()),
                'builder' => $this->scoped(Entity::search($this->query), $this->peopleVaultIds())->where('entity_type_id', $entityType->id),
            ];
        }

        $groups['life-events'] = [
            'label' => __('Life events'),
            'records' => LifeEvent::query()->where('account_id', $this->user->account_id)->whereIn('vault_id', $this->vaultIds()),
            'builder' => $this->scoped(LifeEvent::search($this->query), $this->vaultIds()),
        ];

        return $groups;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $builder
     * @param  list<int>  $vaultIds
     * @return Builder<TModel>
     */
    private function scoped(Builder $builder, array $vaultIds): Builder
    {
        return $builder
            ->where('account_id', $this->user->account_id)
            ->whereIn('vault_id', $vaultIds)
            ->options($this->engineOptions());
    }

    /** @return list<int> */
    private function vaultIds(): array
    {
        return $this->vaultIds ??= $this->acrossAccount
            ? Vault::query()->where('account_id', $this->user->account_id)->pluck('id')->all()
            : [$this->vault->id];
    }

    /** @return list<int> */
    private function peopleVaultIds(): array
    {
        if (! $this->respectsHiddenSections) {
            return $this->vaultIds();
        }

        return $this->peopleVaultIds ??= Vault::query()
            ->whereIn('id', $this->vaultIds())
            ->where('shows_people', true)
            ->pluck('id')
            ->all();
    }

    /**
     * What each engine is told so that every word must match, the name ranks above the values and
     * the values above the linked names, and a sensitive value is out of reach of a caller who may
     * not read it. Engines differ only in how they are told.
     *
     * @return array<string, mixed>
     */
    private function engineOptions(): array
    {
        $attributes = $this->withSensitive
            ? ['name', 'body', 'sensitive_body', 'linked_names']
            : ['name', 'body', 'linked_names'];

        return match (config('scout.driver')) {
            'meilisearch' => [
                'matchingStrategy' => 'all',
                'attributesToSearchOn' => $attributes,
            ],
            'typesense' => [
                'query_by' => implode(',', $attributes),
                'query_by_weights' => implode(',', range(count($attributes), 1)),
                'text_match_type' => 'max_weight',
                'prioritize_exact_match' => true,
                'drop_tokens_threshold' => 0,
                'num_typos' => 2,
            ],
            'algolia' => [
                'restrictSearchableAttributes' => $attributes,
            ],
            'database' => [
                'columns' => array_map(fn (string $attribute): string => "search_{$attribute}", $attributes),
            ],
            default => [],
        };
    }
}
