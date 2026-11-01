<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\DatePrecisionEnum;
use App\Enums\VaultColourEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\FieldValueFormatter;
use App\Helpers\FlagState;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\GroupMember;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Section;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

class ContactTimelineBlock
{
    public const int PER_PAGE = 30;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly int $page = 1,
        private readonly ?int $justAddedId = null,
        private readonly ?int $editingId = null,
        private readonly ?int $editedId = null,
        private readonly int $perPage = self::PER_PAGE,
        private readonly bool $isReadOnly = false,
    ) {}

    /**
     * Whether the timeline is shown away from the contact's page, as on the
     * vault dashboard, where it is only read and leads to the page for more.
     */
    public function isReadOnly(): bool
    {
        return $this->isReadOnly;
    }

    /** @return array<int, array{year: int, countLabel: string, showsHeading: bool, entries: array<int, array{kind: string, id: int|string, icon: string, tint: string, title: string, date: string, details: array<int, string>, narrative: string|null, with: array<int, array{name: string, url: string|null}>, isJustAdded: bool, isEditing: bool, isEdited: bool, editUrl: string, destroyUrl: string|null, deleteLine: string|null}>}> */
    public function years(): array
    {
        return once(function (): array {
            $continuedYear = $this->yearThePreviousPageEndedIn();
            $dated = [];

            foreach ($this->paginator()->items() as $lifeEvent) {
                $dated[] = ['date' => $lifeEvent->happened_on, 'entry' => $this->entry($lifeEvent)];
            }

            $dated = [...$dated, ...$this->membershipEntries()];

            usort($dated, fn (array $a, array $b): int => $b['date']->toDateString() <=> $a['date']->toDateString());

            $years = [];

            foreach ($dated as $item) {
                $year = $item['date']->year;

                $years[$year] ??= [
                    'year' => $year,
                    'countLabel' => trans_choice(':count entry|:count entries', $this->countIn($year)),
                    'showsHeading' => $year !== $continuedYear,
                    'entries' => [],
                ];

                $years[$year]['entries'][] = $item['entry'];
            }

            return array_values($years);
        });
    }

    public function holdsNothing(): bool
    {
        return $this->page === 1 && $this->paginator()->isEmpty() && $this->membershipEntries() === [];
    }

    /**
     * Whether the life event is drawn on this page, where its edit panel can
     * stand in for it. One further down the timeline is not, and the panel
     * then opens above the list instead.
     */
    public function lists(int $lifeEventId): bool
    {
        return collect($this->paginator()->items())->contains('id', $lifeEventId);
    }

    public function nextPageUrl(): ?string
    {
        if ($this->isReadOnly) {
            return null;
        }

        return $this->paginator()->nextPageUrl();
    }

    public function emptyTitle(): string
    {
        return __('Nothing on :name’s timeline yet', ['name' => $this->contact->first_name ?? $this->contact->name]);
    }

    public function emptyLine(): string
    {
        return __('Life events, things you did together and changes to :name’s relationships land here, newest first.', ['name' => $this->contact->first_name ?? $this->contact->name]);
    }

    /** @return Paginator<int, LifeEvent> */
    private function paginator(): Paginator
    {
        return once(fn (): Paginator => $this->query()
            ->with([
                'lifeEventType',
                'participants.participant',
                'fieldValues' => fn ($query) => $query
                    ->attached()
                    ->whereNull('field_value_row_id')
                    ->with(['field', 'valueListItem', 'file']),
            ])
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->simplePaginate($this->perPage, ['*'], 'timelinePage', $this->page));
    }

    /** @return Builder<LifeEvent> */
    private function query(): Builder
    {
        return LifeEvent::query()
            ->where('account_id', $this->contact->account_id)
            ->where('vault_id', $this->vault->id)
            ->whereIn('id', LifeEventParticipant::query()
                ->select('life_event_id')
                ->where('participant_type', $this->contact->getMorphClass())
                ->where('participant_id', $this->contact->id));
    }

    private function countIn(int $year): int
    {
        $memberships = $this->showsMemberships() ? $this->memberships()
            ->filter(fn (GroupMember $membership): bool => $membership->joined_on?->year === $year || $membership->left_on?->year === $year)
            ->sum(fn (GroupMember $membership): int => (int) ($membership->joined_on?->year === $year) + (int) ($membership->left_on?->year === $year)) : 0;

        return $memberships + $this->query()
            ->where('happened_on', '>=', CarbonImmutable::create($year)->toDateString())
            ->where('happened_on', '<', CarbonImmutable::create($year + 1)->toDateString())
            ->count();
    }

    /**
     * A later page carries on the list where the previous one stopped, so a
     * year the previous page was already in does not get its heading twice.
     */
    private function yearThePreviousPageEndedIn(): ?int
    {
        if ($this->page === 1) {
            return null;
        }

        return $this->query()
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->skip(($this->page - 1) * $this->perPage - 1)
            ->first()
            ?->happened_on->year;
    }

    /** @return array{kind: string, id: int, icon: string, tint: string, title: string, date: string, details: array<int, string>, narrative: string|null, with: array<int, array{name: string, url: string|null}>, isJustAdded: bool, isEditing: bool, isEdited: bool, editUrl: string, destroyUrl: string, deleteLine: string} */
    private function entry(LifeEvent $lifeEvent): array
    {
        $icon = $lifeEvent->lifeEventType->icon;

        $others = $lifeEvent->participants
            ->map(fn (LifeEventParticipant $participant): mixed => $participant->participant)
            ->filter(fn (mixed $participant): bool => $participant instanceof Contact && $participant->id !== $this->contact->id)
            ->map(fn (Contact $participant): array => [
                'name' => $participant->first_name ?? $participant->name,
                'url' => $participant->is_partial
                    ? null
                    : route('contacts.show', ['vault' => $this->vault, 'contact' => $participant]),
            ])
            ->values()
            ->all();

        return [
            'kind' => 'lifeEvent',
            'id' => $lifeEvent->id,
            'icon' => in_array($icon, Section::ICONS, true) ? $icon : Section::DEFAULT_ICON,
            'tint' => VaultColourEnum::tintFor($lifeEvent->lifeEventType->colour),
            'title' => $lifeEvent->name,
            'date' => $this->write($lifeEvent->happened_on, $this->withoutTheYear($this->dateFormat())),
            'details' => $this->detailsOf($lifeEvent),
            'narrative' => $lifeEvent->narrative,
            'with' => $others,
            'isJustAdded' => $lifeEvent->id === $this->justAddedId,
            'isEditing' => $lifeEvent->id === $this->editingId,
            'isEdited' => $lifeEvent->id === $this->editedId && $lifeEvent->id !== $this->editingId,
            'editUrl' => route('contacts.lifeEvents.edit', ['vault' => $this->vault, 'contact' => $this->contact, 'lifeEvent' => $lifeEvent]),
            'destroyUrl' => route('contacts.lifeEvents.destroy', ['vault' => $this->vault, 'contact' => $this->contact, 'lifeEvent' => $lifeEvent]),
            'deleteLine' => __('It is removed from the timeline of :names. The people stay.', ['names' => $this->namesOf($lifeEvent)]),
        ];
    }

    /**
     * The first names of everybody who was there, the contact whose page this
     * is included, as one list.
     */
    private function namesOf(LifeEvent $lifeEvent): string
    {
        $names = $lifeEvent->participants
            ->map(fn (LifeEventParticipant $participant): mixed => $participant->participant)
            ->filter(fn (mixed $participant): bool => $participant instanceof Contact)
            ->map(fn (Contact $participant): string => $participant->first_name ?? $participant->name)
            ->values();

        return $names->count() > 1
            ? __(':names and :last', ['names' => $names->slice(0, -1)->implode(__(', ')), 'last' => $names->last()])
            : (string) $names->first();
    }

    /**
     * What else was written down about the life event, in the order the form
     * asks for it: how long it lasted, where, then every custom field of its
     * type that holds something.
     *
     * @return array<int, string>
     */
    private function detailsOf(LifeEvent $lifeEvent): array
    {
        $details = [];

        if ($lifeEvent->duration_in_minutes !== null) {
            $details[] = trans_choice(':count minute|:count minutes', $lifeEvent->duration_in_minutes);
        }

        if ($lifeEvent->location !== null) {
            $details[] = $lifeEvent->location;
        }

        $formatter = new FieldValueFormatter($this->user);

        foreach ($lifeEvent->fieldValues->sortBy(fn (FieldValue $value): int => $value->field->position) as $value) {
            $written = $formatter->format($value);

            if ($written !== null) {
                $details[] = __(':label: :value', ['label' => $value->field->label, 'value' => $written]);
            }
        }

        return $details;
    }

    private function showsMemberships(): bool
    {
        return FlagState::isOn('groups', $this->user->account);
    }

    /**
     * Every membership of the contact with a known date, in a group that still exists.
     *
     * @return Collection<int, GroupMember>
     */
    private function memberships(): Collection
    {
        return once(fn (): Collection => GroupMember::query()
            ->where('contact_id', $this->contact->id)
            ->where(fn (Builder $query): Builder => $query->whereNotNull('joined_on')->orWhereNotNull('left_on'))
            ->whereHas('group', fn (Builder $query): Builder => $query->where('vault_id', $this->vault->id))
            ->with('group')
            ->get());
    }

    /**
     * The joins and departures that fall between the dates this page of life events covers. The
     * first page reaches up to today and the last page takes everything older, so every entry is on
     * exactly one page.
     *
     * @return list<array{date: CarbonImmutable, entry: array<string, mixed>}>
     */
    private function membershipEntries(): array
    {
        return once(function (): array {
            if (! $this->showsMemberships()) {
                return [];
            }

            $items = $this->paginator()->items();
            $from = $this->paginator()->hasMorePages() && $items !== [] ? end($items)->happened_on->toDateString() : null;
            $until = $this->page === 1 ? null : $this->dateThePreviousPageEndedOn();
            $period = new GroupPeriod($this->user);
            $entries = [];

            foreach ($this->memberships() as $membership) {
                foreach (['joined' => $membership->joined_on, 'left' => $membership->left_on] as $moment => $date) {
                    if ($date === null) {
                        continue;
                    }

                    if ($from !== null && $date->toDateString() < $from) {
                        continue;
                    }

                    if ($until !== null && $date->toDateString() >= $until) {
                        continue;
                    }

                    $precision = $moment === 'joined' ? $membership->joined_precision : $membership->left_precision;

                    $entries[] = ['date' => $date, 'entry' => [
                        'kind' => 'membership',
                        'id' => "membership-{$membership->id}-{$moment}",
                        'icon' => 'people',
                        'tint' => VaultColourEnum::tintFor(null),
                        'title' => $moment === 'joined'
                            ? __('Joined :group', ['group' => $membership->group->name])
                            : __('Left :group', ['group' => $membership->group->name]),
                        'date' => $precision === DatePrecisionEnum::Day
                            ? $this->write($date, $this->withoutTheYear($this->dateFormat()))
                            : (string) $period->date($date, $precision),
                        'details' => $membership->role === null ? [] : [$membership->role],
                        'narrative' => null,
                        'with' => [],
                        'isJustAdded' => false,
                        'isEditing' => false,
                        'isEdited' => false,
                        'editUrl' => route('groups.members.edit', ['vault' => $this->vault, 'group' => $membership->group, 'membership' => $membership]),
                        'destroyUrl' => null,
                        'deleteLine' => null,
                    ]];
                }
            }

            return $entries;
        });
    }

    private function dateThePreviousPageEndedOn(): ?string
    {
        return $this->query()
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->skip(($this->page - 1) * $this->perPage - 1)
            ->first()
            ?->happened_on->toDateString();
    }

    private function write(CarbonInterface $date, string $format): string
    {
        return DateFormatEnum::write($date, $format);
    }

    private function dateFormat(): string
    {
        return $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];
    }

    private function withoutTheYear(string $format): string
    {
        return mb_trim((string) preg_replace('#[/.\-\s]*Y[/.\-\s]*#', ' ', $format));
    }
}
