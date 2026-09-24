<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\VaultColourEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\FieldValueFormatter;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Section;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
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
    ) {}

    /** @return array<int, array{year: int, countLabel: string, showsHeading: bool, entries: array<int, array{id: int, icon: string, tint: string, title: string, date: string, details: array<int, string>, narrative: string|null, with: array<int, array{name: string, url: string|null}>, isJustAdded: bool, isEditing: bool, isEdited: bool, editUrl: string, destroyUrl: string, deleteLine: string}>}> */
    public function years(): array
    {
        return once(function (): array {
            $continuedYear = $this->yearThePreviousPageEndedIn();
            $years = [];

            foreach ($this->paginator()->items() as $lifeEvent) {
                $year = $lifeEvent->happened_on->year;

                $years[$year] ??= [
                    'year' => $year,
                    'countLabel' => trans_choice(':count entry|:count entries', $this->countIn($year)),
                    'showsHeading' => $year !== $continuedYear,
                    'entries' => [],
                ];

                $years[$year]['entries'][] = $this->entry($lifeEvent);
            }

            return array_values($years);
        });
    }

    public function holdsNothing(): bool
    {
        return $this->page === 1 && $this->paginator()->isEmpty();
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
        return $this->paginator()->nextPageUrl();
    }

    /** @return array{title: string, date: string}|null */
    public function creationEntry(): ?array
    {
        if ($this->paginator()->hasMorePages()) {
            return null;
        }

        return [
            'title' => __('You added :name', ['name' => $this->contact->name]),
            'date' => $this->write($this->contact->created_at, $this->dateFormat()),
        ];
    }

    public function emptyTitle(): string
    {
        return __('Nothing else yet');
    }

    public function emptyLine(): string
    {
        return __('Moves, new jobs, weddings and the things you do together land here with their date, newest first.');
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
            ->simplePaginate(self::PER_PAGE, ['*'], 'timelinePage', $this->page));
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
        return $this->query()
            ->whereYear('happened_on', $year)
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
            ->skip(($this->page - 1) * self::PER_PAGE - 1)
            ->first()
            ?->happened_on->year;
    }

    /** @return array{id: int, icon: string, tint: string, title: string, date: string, details: array<int, string>, narrative: string|null, with: array<int, array{name: string, url: string|null}>, isJustAdded: bool, isEditing: bool, isEdited: bool, editUrl: string, destroyUrl: string, deleteLine: string} */
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
