<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\DatePrecisionEnum;
use App\Enums\VaultColourEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\LifeEventTimeline;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventType;
use App\Models\Section;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class DashboardTimelineBlock
{
    public const int PER_PAGE = 100;

    private const int RELATIVE_DAYS = 56;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly ?string $slice = null,
        private readonly ?string $contact = null,
        private readonly ?string $type = null,
        private readonly int $page = 1,
        private readonly ?int $justAddedId = null,
    ) {}

    public function total(): int
    {
        return $this->timeline()->total();
    }

    public function holdsNothing(): bool
    {
        return $this->total() === 0;
    }

    public function isEmptySlice(): bool
    {
        return ! $this->holdsNothing() && $this->lifeEvents() === [];
    }

    public function page(): int
    {
        return $this->page;
    }

    /** @return list<array{label: string, count: int, url: string, isCurrent: bool, startsYears: bool}> */
    public function index(): array
    {
        $index = [];

        foreach ($this->timeline()->slices() as $position => $slice) {
            $index[] = [
                'label' => $this->labelOf($slice['slice']),
                'count' => $slice['count'],
                'url' => $this->url(slice: $slice['slice']),
                'isCurrent' => $slice['slice'] === $this->selectedSlice(),
                'startsYears' => $position === 1,
            ];
        }

        return $index;
    }

    public function title(): string
    {
        return $this->labelOf($this->selectedSlice());
    }

    public function countLabel(): string
    {
        $count = collect($this->timeline()->slices())->firstWhere('slice', $this->selectedSlice())['count'] ?? 0;

        return trans_choice(':count entry|:count entries', $count);
    }

    /** @return list<array{key: string, label: string, showsHeading: bool, entries: list<array{id: int, icon: string, tint: string, title: string, typeName: string, date: string, relative: string|null, with: list<array{name: string, url: string|null}>, editUrl: string|null, isJustAdded: bool}>}> */
    public function months(): array
    {
        $months = [];
        $imprecise = [];
        $continued = $this->groupThePreviousPageEndedIn();
        $isUpcoming = $this->selectedSlice() === LifeEventTimeline::UPCOMING;

        foreach ($this->lifeEvents() as $lifeEvent) {
            $date = $lifeEvent->happened_on;
            $key = $this->groupOf($lifeEvent);

            if ($key === "{$date->year}-sometime") {
                $imprecise[$date->year] ??= [
                    'key' => $key,
                    'label' => __('Sometime in :year', ['year' => $date->year]),
                    'showsHeading' => $key !== $continued,
                    'entries' => [],
                ];
                $imprecise[$date->year]['entries'][] = $this->entry($lifeEvent);

                continue;
            }

            $months[$key] ??= [
                'key' => $key,
                'label' => DateFormatEnum::write($date, $isUpcoming ? 'F Y' : 'F'),
                'showsHeading' => $key !== $continued,
                'entries' => [],
            ];
            $months[$key]['entries'][] = $this->entry($lifeEvent);
        }

        $groups = [];

        foreach ($months as $key => $month) {
            $year = (int) substr($key, 0, 4);

            foreach ($imprecise as $impreciseYear => $group) {
                if ($isUpcoming && $impreciseYear < $year) {
                    $groups[] = $group;
                    unset($imprecise[$impreciseYear]);
                }
            }

            $groups[] = $month;
        }

        return [...$groups, ...array_values($imprecise)];
    }

    public function nextPageUrl(): ?string
    {
        if ($this->fetched()->count() <= self::PER_PAGE + ($this->page > 1 ? 1 : 0)) {
            return null;
        }

        return $this->url(slice: $this->selectedSlice(), page: $this->page + 1);
    }

    /** @return array{label: string, url: string}|null */
    public function newer(): ?array
    {
        $slices = array_column($this->timeline()->slices(), 'slice');
        $position = array_search($this->selectedSlice(), $slices, true);

        if ($position === false || $position === 0) {
            return null;
        }

        return [
            'label' => __('Newer: :slice', ['slice' => $this->labelOf($slices[$position - 1])]),
            'url' => $this->url(slice: $slices[$position - 1]),
        ];
    }

    /** @return array{label: string, url: string}|null */
    public function older(): ?array
    {
        $slices = array_column($this->timeline()->slices(), 'slice');
        $position = array_search($this->selectedSlice(), $slices, true);

        if ($position === false || ! isset($slices[$position + 1])) {
            return null;
        }

        return [
            'label' => __('Older: :slice', ['slice' => $this->labelOf($slices[$position + 1])]),
            'url' => $this->url(slice: $slices[$position + 1]),
        ];
    }

    public function selectedSlice(): string
    {
        return once(fn (): string => $this->slice !== null && $this->timeline()->holds($this->slice)
            ? $this->slice
            : $this->timeline()->defaultSlice());
    }

    /** @return array{uuid: string, name: string}|null */
    public function contactChoice(): ?array
    {
        $contact = $this->chosenContact();

        return $contact instanceof Contact ? ['uuid' => $contact->uuid, 'name' => $contact->name] : null;
    }

    public function contactSearchUrl(): string
    {
        return route('vaults.timelineContactCandidates.index', ['vault' => $this->vault]);
    }

    public function chosenTypeUuid(): ?string
    {
        return $this->chosenType()?->uuid;
    }

    /** @return list<array{category: string, types: list<array{uuid: string, name: string, isSelected: bool}>}> */
    public function typeOptions(): array
    {
        return $this->types()
            ->groupBy('life_event_category_id')
            ->map(fn (Collection $types): array => [
                'category' => (string) $types->first()?->lifeEventCategory?->name,
                'types' => $types->map(fn (LifeEventType $type): array => [
                    'uuid' => $type->uuid,
                    'name' => $type->name,
                    'isSelected' => $type->uuid === $this->chosenTypeUuid(),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    public function isFiltered(): bool
    {
        return $this->chosenContact() instanceof Contact || $this->chosenType() instanceof LifeEventType;
    }

    public function clearFiltersUrl(): string
    {
        return route('vaults.show', ['vault' => $this->vault, 'slice' => $this->selectedSlice()]);
    }

    public function filterUrl(): string
    {
        return route('vaults.show', ['vault' => $this->vault]);
    }

    private function timeline(): LifeEventTimeline
    {
        return once(fn (): LifeEventTimeline => new LifeEventTimeline(
            vault: $this->vault,
            today: $this->today(),
            type: $this->chosenType(),
            contact: $this->chosenContact(),
        ));
    }

    private function chosenType(): ?LifeEventType
    {
        if ($this->type === null) {
            return null;
        }

        return $this->types()->firstWhere('uuid', $this->type);
    }

    /** @return Collection<int, LifeEventType> */
    private function types(): Collection
    {
        return once(fn (): Collection => LifeEventTimeline::typesIn($this->vault));
    }

    private function chosenContact(): ?Contact
    {
        if ($this->contact === null) {
            return null;
        }

        return once(fn (): ?Contact => Contact::query()
            ->where('vault_id', $this->vault->id)
            ->where('uuid', $this->contact)
            ->first());
    }

    /**
     * The life events of the page. A later page also fetches the last one of the previous page, to
     * know which month it continues, and every page one more, to know whether another follows.
     *
     * @return Collection<int, LifeEvent>
     */
    private function fetched(): Collection
    {
        if ($this->holdsNothing()) {
            return new Collection;
        }

        return once(fn (): Collection => $this->timeline()
            ->query($this->selectedSlice())
            ->select(['life_events.id', 'life_events.uuid', 'life_events.life_event_type_id', 'life_events.title', 'life_events.happened_on', 'life_events.date_precision'])
            ->offset(max(0, ($this->page - 1) * self::PER_PAGE - 1))
            ->limit(self::PER_PAGE + ($this->page > 1 ? 2 : 1))
            ->get());
    }

    /** @return list<LifeEvent> */
    private function lifeEvents(): array
    {
        return array_values($this->fetched()->slice($this->page > 1 ? 1 : 0, self::PER_PAGE)->all());
    }

    /**
     * Every contact who took part in a life event of the page, in one read, grouped by life event.
     *
     * @return SupportCollection<int|string, Collection<int, Contact>>
     */
    private function people(): SupportCollection
    {
        return once(function (): SupportCollection {
            $ids = array_map(fn (LifeEvent $lifeEvent): int => $lifeEvent->id, $this->lifeEvents());

            if ($ids === []) {
                return collect();
            }

            return Contact::query()
                ->join('life_event_participants', 'life_event_participants.participant_id', '=', 'contacts.id')
                ->where('life_event_participants.participant_type', new Contact()->getMorphClass())
                ->whereIn('life_event_participants.life_event_id', $ids)
                ->orderBy('life_event_participants.position')
                ->select('contacts.*', 'life_event_participants.life_event_id as timeline_life_event_id')
                ->get()
                ->groupBy('timeline_life_event_id');
        });
    }

    /** @return array{id: int, icon: string, tint: string, title: string, typeName: string, date: string, relative: string|null, with: list<array{name: string, url: string|null}>, editUrl: string|null, isJustAdded: bool} */
    private function entry(LifeEvent $lifeEvent): array
    {
        $type = $this->types()->firstWhere('id', $lifeEvent->life_event_type_id);
        $icon = $type?->icon;
        $people = $this->people()->get($lifeEvent->id, new Collection);
        $editable = $people->first(fn (Contact $contact): bool => ! $contact->is_partial);

        return [
            'id' => $lifeEvent->id,
            'icon' => in_array($icon, Section::ICONS, true) ? $icon : Section::DEFAULT_ICON,
            'tint' => VaultColourEnum::tintFor($type?->colour),
            'title' => $lifeEvent->title ?? (string) $type?->name,
            'typeName' => (string) $type?->name,
            'date' => $this->dateOf($lifeEvent),
            'relative' => $lifeEvent->date_precision === DatePrecisionEnum::Day ? $this->relative($lifeEvent->happened_on) : null,
            'with' => $people->map(fn (Contact $contact): array => [
                'name' => $contact->name,
                'url' => $contact->is_partial ? null : route('contacts.show', ['vault' => $this->vault, 'contact' => $contact]),
            ])->values()->all(),
            'editUrl' => $editable === null
                ? null
                : route('contacts.lifeEvents.edit', ['vault' => $this->vault, 'contact' => $editable, 'lifeEvent' => $lifeEvent]),
            'isJustAdded' => $lifeEvent->id === $this->justAddedId,
        ];
    }

    private function dateOf(LifeEvent $lifeEvent): string
    {
        $day = $lifeEvent->happened_on;

        return match ($lifeEvent->date_precision) {
            DatePrecisionEnum::Day => DateFormatEnum::write($day, $this->dayFormat()),
            DatePrecisionEnum::Month => DateFormatEnum::write($day, 'F'),
            DatePrecisionEnum::Year => (string) $day->year,
            DatePrecisionEnum::Decade => __('Between :start and :end', ['start' => intdiv($day->year, 10) * 10, 'end' => intdiv($day->year, 10) * 10 + 9]),
            DatePrecisionEnum::Unknown => __('Date unknown'),
        };
    }

    private function relative(CarbonImmutable $day): ?string
    {
        // The stored day has no time zone: read it as that day where the reader is, not as midnight UTC.
        $day = $day->shiftTimezone($this->user->timezone)->startOfDay();
        $days = (int) $this->today()->diffInDays($day, false);

        if (abs($days) > self::RELATIVE_DAYS) {
            return null;
        }

        return match (true) {
            $day->isSameDay($this->today()) => __('Today'),
            $day->isSameDay($this->today()->addDay()) => __('Tomorrow'),
            $day->isSameDay($this->today()->subDay()) => __('Yesterday'),
            $days > 0 && $days < 14 => trans_choice('in :count day|in :count days', $days),
            $days > 0 => trans_choice('in :count week|in :count weeks', (int) round($days / 7)),
            $days > -14 => trans_choice(':count day ago|:count days ago', -$days),
            default => trans_choice(':count week ago|:count weeks ago', (int) round(-$days / 7)),
        };
    }

    private function groupThePreviousPageEndedIn(): ?string
    {
        if ($this->page === 1) {
            return null;
        }

        $last = $this->fetched()->first();

        return $last instanceof LifeEvent ? $this->groupOf($last) : null;
    }

    private function groupOf(LifeEvent $lifeEvent): string
    {
        return in_array($lifeEvent->date_precision, [DatePrecisionEnum::Day, DatePrecisionEnum::Month], true)
            ? $lifeEvent->happened_on->format('Y-m')
            : "{$lifeEvent->happened_on->year}-sometime";
    }

    private function labelOf(string $slice): string
    {
        return $slice === LifeEventTimeline::UPCOMING ? __('Upcoming') : $slice;
    }

    private function url(string $slice, int $page = 1): string
    {
        return route('vaults.show', array_filter([
            'vault' => $this->vault,
            'slice' => $slice,
            'contact' => $this->chosenContact()?->uuid,
            'type' => $this->chosenType()?->uuid,
            'page' => $page > 1 ? $page : null,
        ], fn (mixed $value): bool => $value !== null));
    }

    private function dayFormat(): string
    {
        $format = $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];

        return mb_trim((string) preg_replace('#[/.\-\s]*Y[/.\-\s]*#', ' ', $format));
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now($this->user->timezone)->startOfDay();
    }
}
