<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventType;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * The life events of a vault cut into slices: what is upcoming, then one slice per year before today.
 * Every count comes from one aggregate, so reading the index costs the same whatever the vault holds.
 * The dashboard and the API read through it, so both give the same answer.
 */
class LifeEventTimeline
{
    public const string UPCOMING = 'upcoming';

    public const string EVERYTHING = 'everything';

    public function __construct(
        private readonly Vault $vault,
        private readonly CarbonImmutable $today,
        private readonly ?LifeEventType $type = null,
        private readonly ?Contact $contact = null,
    ) {}

    /**
     * Compared as days, as the queries compare them: comparing moments puts today in the past west of UTC.
     */
    public static function sliceOf(CarbonImmutable $day, CarbonImmutable $today): string
    {
        return $day->toDateString() >= $today->toDateString() ? self::UPCOMING : (string) $day->year;
    }

    /** @return list<array{slice: string, count: int, total: int}> */
    public function slices(): array
    {
        return once(function (): array {
            $rows = $this->counts();
            $slices = [[
                'slice' => self::UPCOMING,
                'count' => (int) $rows->sum('upcoming'),
                'total' => (int) $rows->sum('upcoming_all'),
            ]];

            foreach ($rows->sortByDesc('year') as $row) {
                if ((int) $row->past_all === 0) {
                    continue;
                }

                $slices[] = [
                    'slice' => (string) $row->year,
                    'count' => (int) $row->past,
                    'total' => (int) $row->past_all,
                ];
            }

            return $slices;
        });
    }

    public function total(): int
    {
        $rows = $this->counts();

        return (int) $rows->sum('past_all') + (int) $rows->sum('upcoming_all');
    }

    public function defaultSlice(): string
    {
        foreach ($this->slices() as $slice) {
            if ($slice['count'] > 0) {
                return $slice['slice'];
            }
        }

        return self::UPCOMING;
    }

    public function holds(string $slice): bool
    {
        return in_array($slice, array_column($this->slices(), 'slice'), true);
    }

    /** @return Builder<LifeEvent> */
    public function query(string $slice): Builder
    {
        $query = $this->filtered(LifeEvent::query());

        if ($slice === self::EVERYTHING) {
            return $query
                ->orderByDesc('happened_on')
                ->orderByDesc('id');
        }

        if ($slice === self::UPCOMING) {
            return $query
                ->where('happened_on', '>=', $this->today->toDateString())
                ->orderBy('happened_on')
                ->orderBy('id');
        }

        $start = CarbonImmutable::create((int) $slice);
        $end = $start->addYear()->min($this->today);

        return $query
            ->where('happened_on', '>=', $start->toDateString())
            ->where('happened_on', '<', $end->toDateString())
            ->orderByDesc('happened_on')
            ->orderByDesc('id');
    }

    /**
     * A life event keeps the type it was written with, so a deleted type is still listed.
     *
     * @return Collection<int, LifeEventType>
     */
    public static function typesIn(Vault $vault): Collection
    {
        return LifeEventType::withTrashed()
            ->whereIn('id', LifeEvent::query()
                ->select('life_event_type_id')
                ->where('vault_id', $vault->id)
                ->distinct())
            ->with(['lifeEventCategory' => fn (BelongsTo $query): BelongsTo => $query->withTrashed()])
            ->get()
            ->sortBy([
                fn (LifeEventType $a, LifeEventType $b): int => $a->lifeEventCategory->position <=> $b->lifeEventCategory->position,
                fn (LifeEventType $a, LifeEventType $b): int => $a->position <=> $b->position,
            ])
            ->values();
    }

    /** @return SupportCollection<int, stdClass> */
    private function counts(): SupportCollection
    {
        return once(function (): SupportCollection {
            $today = $this->today->toDateString();
            $condition = $this->matchingCondition();
            $year = $this->yearOf('happened_on');

            return DB::table('life_events')
                ->where('vault_id', $this->vault->id)
                ->whereNull('deleted_at')
                ->selectRaw("{$year} as year")
                ->selectRaw('sum(case when happened_on < ? then 1 else 0 end) as past_all', [$today])
                ->selectRaw('sum(case when happened_on >= ? then 1 else 0 end) as upcoming_all', [$today])
                ->selectRaw("sum(case when happened_on < ? and {$condition['sql']} then 1 else 0 end) as past", [$today, ...$condition['bindings']])
                ->selectRaw("sum(case when happened_on >= ? and {$condition['sql']} then 1 else 0 end) as upcoming", [$today, ...$condition['bindings']])
                ->groupByRaw($year)
                ->get();
        });
    }

    /** @return array{sql: string, bindings: list<int|string>} */
    private function matchingCondition(): array
    {
        $sql = ['1 = 1'];
        $bindings = [];

        if ($this->type instanceof LifeEventType) {
            $sql[] = 'life_events.life_event_type_id = ?';
            $bindings[] = $this->type->id;
        }

        if ($this->contact instanceof Contact) {
            $sql[] = 'exists (select 1 from life_event_participants where life_event_participants.life_event_id = life_events.id and life_event_participants.participant_type = ? and life_event_participants.participant_id = ?)';
            $bindings[] = $this->contact->getMorphClass();
            $bindings[] = $this->contact->id;
        }

        return ['sql' => implode(' and ', $sql), 'bindings' => $bindings];
    }

    /**
     * @param  Builder<LifeEvent>  $query
     * @return Builder<LifeEvent>
     */
    private function filtered(Builder $query): Builder
    {
        $query->where('life_events.vault_id', $this->vault->id);

        if ($this->type instanceof LifeEventType) {
            $query->where('life_events.life_event_type_id', $this->type->id);
        }

        if ($this->contact instanceof Contact) {
            $query->whereExists(fn (QueryBuilder $participants): QueryBuilder => $participants
                ->selectRaw('1')
                ->from('life_event_participants')
                ->whereColumn('life_event_participants.life_event_id', 'life_events.id')
                ->where('life_event_participants.participant_type', $this->contact->getMorphClass())
                ->where('life_event_participants.participant_id', $this->contact->id));
        }

        return $query;
    }

    /**
     * Each database the application runs on spells the year of a date its own way.
     */
    private function yearOf(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => "year({$column})",
            'pgsql' => "cast(extract(year from {$column}) as integer)",
            default => "cast(strftime('%Y', {$column}) as integer)",
        };
    }
}
