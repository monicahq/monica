<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\TrashItemKindEnum;
use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\Entity;
use App\Models\Group;
use App\Models\Vault;
use DateTimeInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * What waits in the trash of a vault. The nine kinds live in nine tables, so they are read as one
 * union, sorted and paged by the database, and only the records of the page are then loaded.
 */
class VaultTrash
{
    public function __construct(
        private readonly Vault $vault,
    ) {}

    /**
     * Everything in the trash, newest first. Without a kind, an item deleted with a person or a
     * group still in the trash is left out, since it comes back with its owner.
     *
     * @return LengthAwarePaginator<int, TrashItem>
     */
    public function paginate(?TrashItemKindEnum $kind, int $perPage, int $page = 1): LengthAwarePaginator
    {
        $kinds = $kind === null ? $this->kinds() : array_values(array_filter($this->kinds(), fn (TrashItemKindEnum $available): bool => $available === $kind));

        if ($kinds === []) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }

        $rows = DB::query()
            ->fromSub($this->union($kinds), 'trash')
            ->when($kind === null, fn (Builder $query): Builder => $query->whereNull('deleted_with_type'))
            ->orderByDesc('deleted_at')
            ->orderBy('kind')
            ->orderByDesc('id')
            ->paginate(perPage: $perPage, page: $page);

        $items = $this->load(collect($rows->items()));

        return new LengthAwarePaginator($items, $rows->total(), $perPage, $rows->currentPage());
    }

    /**
     * How many items of each kind wait in the trash, and how many are listed under Everything.
     *
     * @return array{total: int, kinds: array<string, int>}
     */
    public function counts(): array
    {
        $kinds = $this->kinds();

        if ($kinds === []) {
            return ['total' => 0, 'kinds' => []];
        }

        $rows = DB::query()
            ->fromSub($this->union($kinds), 'trash')
            ->selectRaw('kind, count(*) as items, sum(case when deleted_with_type is null then 1 else 0 end) as top_level')
            ->groupBy('kind')
            ->get();

        return [
            'total' => (int) $rows->sum('top_level'),
            'kinds' => $rows->mapWithKeys(fn (object $row): array => [(string) $row->kind => (int) $row->items])->all(),
        ];
    }

    /**
     * One item of the trash, or not found when it is not in this vault's trash.
     */
    public function find(TrashItemKindEnum $kind, string $uuid): TrashItem
    {
        if (! in_array($kind, $this->kinds(), true)) {
            throw new ModelNotFoundException('This kind of record is not available.');
        }

        $row = DB::query()
            ->fromSub($this->union([$kind]), 'trash')
            ->where('uuid', $uuid)
            ->first();

        if ($row === null) {
            throw new ModelNotFoundException('This record is not in the trash of this vault.');
        }

        return $this->load(collect([$row]))->first();
    }

    /**
     * How many items of each kind went to the trash with each of the given people and groups,
     * keyed by the owner's class and id.
     *
     * @param  iterable<TrashItem>  $owners
     * @return array<string, array<string, int>>
     */
    public function dependentCounts(iterable $owners): array
    {
        $pairs = collect($owners)
            ->filter(fn (TrashItem $item): bool => in_array($item->kind, [TrashItemKindEnum::Contact, TrashItemKindEnum::Group], true))
            ->map(fn (TrashItem $item): array => [$item->record->getMorphClass(), $item->record->getKey()]);

        if ($pairs->isEmpty()) {
            return [];
        }

        $kinds = array_values(array_filter($this->kinds(), fn (TrashItemKindEnum $kind): bool => ! in_array($kind, [TrashItemKindEnum::Contact, TrashItemKindEnum::Entity, TrashItemKindEnum::Group], true)));

        if ($kinds === []) {
            return [];
        }

        $rows = DB::query()
            ->fromSub($this->union($kinds, withOwnerId: true), 'trash')
            ->where(function (Builder $query) use ($pairs): void {
                foreach ($pairs as [$type, $id]) {
                    $query->orWhere(fn (Builder $pair): Builder => $pair->where('deleted_with_type', $type)->where('deleted_with_id', $id));
                }
            })
            ->selectRaw('deleted_with_type, deleted_with_id, kind, count(*) as items')
            ->groupBy('deleted_with_type', 'deleted_with_id', 'kind')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $counts["{$row->deleted_with_type}:{$row->deleted_with_id}"][(string) $row->kind] = (int) $row->items;
        }

        return $counts;
    }

    /**
     * Every item of the trash that is not waiting for an owner, oldest first.
     *
     * @return Collection<int, TrashItem>
     */
    public function everything(): Collection
    {
        $kinds = $this->kinds();

        if ($kinds === []) {
            return collect();
        }

        $rows = DB::query()
            ->fromSub($this->union($kinds), 'trash')
            ->whereNull('deleted_with_type')
            ->orderBy('deleted_at')
            ->get();

        return $this->load($rows);
    }

    /**
     * The items of the trash deleted before a moment, oldest first, for the daily purge. A kind behind
     * a flag that is off for the account is left alone, since its data is kept while it is withdrawn.
     *
     * @return Collection<int, TrashItem>
     */
    public function expiredBefore(DateTimeInterface $moment): Collection
    {
        $kinds = $this->kinds();

        if ($kinds === []) {
            return collect();
        }

        $rows = DB::query()
            ->fromSub($this->union($kinds), 'trash')
            ->whereNull('deleted_with_type')
            ->where('deleted_at', '<=', $moment)
            ->orderBy('deleted_at')
            ->get();

        return $this->load($rows);
    }

    /** @return list<TrashItemKindEnum> */
    private function kinds(): array
    {
        return TrashItemKindEnum::availableFor($this->vault->account);
    }

    /** @param list<TrashItemKindEnum> $kinds */
    private function union(array $kinds, bool $withOwnerId = false): Builder
    {
        $queries = array_map(fn (TrashItemKindEnum $kind): Builder => $this->queryFor($kind, $withOwnerId), $kinds);
        $union = array_shift($queries);

        foreach ($queries as $query) {
            $union->unionAll($query);
        }

        return $union;
    }

    private function queryFor(TrashItemKindEnum $kind, bool $withOwnerId): Builder
    {
        $table = new ($kind->model())()->getTable();

        $query = DB::table($table)
            ->selectRaw('? as kind', [$kind->value])
            ->addSelect(["{$table}.id", "{$table}.uuid", "{$table}.deleted_at"])
            ->whereNotNull("{$table}.deleted_at");

        $throughTheGroup = in_array($kind, [TrashItemKindEnum::GroupMembership, TrashItemKindEnum::GroupFile], true);

        $query
            ->when($throughTheGroup, fn (Builder $query): Builder => $query->join('groups', 'groups.id', '=', "{$table}.group_id")->where('groups.vault_id', $this->vault->id))
            ->unless($throughTheGroup, fn (Builder $query): Builder => $query->where("{$table}.vault_id", $this->vault->id));

        return match ($kind) {
            TrashItemKindEnum::Contact => $query
                ->selectRaw('null as deleted_with_type')
                ->where('contacts.deleted_with_vault', false)
                ->whereNotExists(fn (Builder $merges): Builder => $merges
                    ->selectRaw('1')
                    ->from('contact_merges')
                    ->whereColumn('contact_merges.merged_contact_id', 'contacts.id')
                    ->whereNull('contact_merges.undone_at')),
            TrashItemKindEnum::Entity, TrashItemKindEnum::Group => $query
                ->selectRaw('null as deleted_with_type')
                ->where("{$table}.deleted_with_vault", false),
            default => $query
                ->addSelect("{$table}.deleted_with_type")
                ->when($withOwnerId, fn (Builder $owner): Builder => $owner->addSelect("{$table}.deleted_with_id"))
                ->where(fn (Builder $owner): Builder => $owner
                    ->whereNull("{$table}.deleted_with_type")
                    ->orWhere("{$table}.deleted_with_type", '!=', ContactMerge::class)),
        };
    }

    /**
     * Load the records behind union rows, keeping their order.
     *
     * @param  Collection<int, stdClass>  $rows
     * @return Collection<int, TrashItem>
     */
    private function load(Collection $rows): Collection
    {
        $records = $rows->groupBy('kind')->map(function (Collection $rowsOfKind, string $value): Collection {
            $kind = TrashItemKindEnum::from($value);

            return $kind->model()::query()
                ->withoutGlobalScope(SoftDeletingScope::class)
                ->with($this->relationsFor($kind))
                ->whereKey($rowsOfKind->pluck('id')->all())
                ->get()
                ->keyBy('id');
        });

        return $rows
            ->map(fn (object $row): ?TrashItem => isset($records[$row->kind][$row->id])
                ? new TrashItem(kind: TrashItemKindEnum::from($row->kind), record: $records[$row->kind][$row->id])
                : null)
            ->filter()
            ->values();
    }

    /** @return array<string|int, mixed> */
    private function relationsFor(TrashItemKindEnum $kind): array
    {
        $withTrashed = fn ($query) => $query->withTrashed();
        $anyTrashed = fn (MorphTo $query) => $query->constrain([
            Contact::class => $withTrashed,
            Entity::class => $withTrashed,
            Group::class => $withTrashed,
        ]);
        $owner = ['deletedWith' => $anyTrashed];

        return match ($kind) {
            TrashItemKindEnum::Contact, TrashItemKindEnum::Group => [],
            TrashItemKindEnum::Entity => ['entityType' => $withTrashed],
            TrashItemKindEnum::Relationship => ['contactA' => $withTrashed, 'contactB' => $withTrashed, 'relationshipType' => $withTrashed, ...$owner],
            TrashItemKindEnum::LifeEvent => ['lifeEventType' => $withTrashed, 'participants.participant' => $anyTrashed, ...$owner],
            TrashItemKindEnum::Reminder => ['remindable' => $anyTrashed, ...$owner],
            TrashItemKindEnum::GroupMembership => ['contact' => $withTrashed, 'group' => $withTrashed, ...$owner],
            TrashItemKindEnum::GroupNote => ['group' => $withTrashed, ...$owner],
            TrashItemKindEnum::GroupFile => ['group' => $withTrashed, 'file' => $withTrashed, ...$owner],
        };
    }
}
