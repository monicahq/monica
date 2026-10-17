<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderOccurrenceCategoryEnum;
use App\Enums\ReminderSourceEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\NextReminderDeadlines;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

class ReminderViewModel
{
    private const int HISTORY_PER_PAGE = 4;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Reminder $reminder,
        private readonly int $deliveryPage = 1,
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
        return $this->reminder->name;
    }

    public function remindersUrl(): string
    {
        return route('reminders.index', $this->vault);
    }

    /** @return array{name: string, url: string, avatarUrl: string|null}|null */
    public function contact(): ?array
    {
        $contact = $this->reminder->remindable;

        if (! $contact instanceof Contact) {
            return null;
        }

        return [
            'name' => $contact->name,
            'url' => route('contacts.show', ['vault' => $this->vault, 'contact' => $contact]),
            'avatarUrl' => $contact->avatar === null
                ? null
                : route('files.variants.show', ['vault' => $this->vault, 'file' => $contact->avatar, 'size' => Contact::AVATAR_THUMBNAIL_PIXELS]),
        ];
    }

    public function contactRemindersUrl(): ?string
    {
        $contact = $this->reminder->remindable;

        if (! $contact instanceof Contact) {
            return null;
        }

        return route('contacts.reminders.index', ['vault' => $this->vault, 'contact' => $contact]);
    }

    public function scheduleLine(): string
    {
        $anchor = $this->reminder->source === ReminderSourceEnum::Manual
            ? $this->reminder->starts_on
            : $this->reminder->fieldValue?->value_date;
        $interval = $this->reminder->frequency_interval;
        $words = [
            'time' => $this->user->time_format->format(CarbonImmutable::parse($this->reminder->time_of_day, $this->reminder->timezone)),
            'zone' => $this->reminder->timezone,
        ];

        if ($anchor === null || $interval === 0) {
            return __('At :time · :zone.', $words);
        }

        $anchor = $anchor->locale(app()->getLocale());

        return match ($this->reminder->cadence) {
            ReminderCadenceEnum::Once => __('Once, on :date at :time · :zone.', [
                ...$words,
                'date' => DateFormatEnum::write($anchor, $this->dateFormat()),
            ]),
            ReminderCadenceEnum::Weekly => trans_choice(
                '{1}Every week on :weekday at :time · :zone.|Every :count weeks on :weekday at :time · :zone.',
                $interval,
                [...$words, 'weekday' => $anchor->isoFormat('dddd')],
            ),
            ReminderCadenceEnum::Monthly => trans_choice(
                '{1}Every month on day :day at :time · :zone.|Every :count months on day :day at :time · :zone.',
                $interval,
                [...$words, 'day' => $anchor->day],
            ),
            ReminderCadenceEnum::Yearly => trans_choice(
                '{1}Every year on :date at :time · :zone.|Every :count years on :date at :time · :zone.',
                $interval,
                [...$words, 'date' => $anchor->isoFormat(str_starts_with($this->dateFormat(), 'm') ? 'MMMM D' : 'D MMMM')],
            ),
        };
    }

    public function sourceLine(): ?string
    {
        if ($this->reminder->source === ReminderSourceEnum::Manual) {
            return null;
        }

        return __('Based on :name’s “:label”', [
            'name' => $this->reminder->remindable->name,
            'label' => $this->reminder->field->label ?? '',
        ]);
    }

    public function editUrl(): ?string
    {
        $contact = $this->reminder->remindable;

        if ($this->reminder->source !== ReminderSourceEnum::Manual || ! $contact instanceof Contact) {
            return null;
        }

        return route('contacts.reminders.edit', ['vault' => $this->vault, 'contact' => $contact, 'reminder' => $this->reminder]);
    }

    public function editDateUrl(): ?string
    {
        $contact = $this->reminder->remindable;

        if ($this->reminder->source === ReminderSourceEnum::Manual || ! $contact instanceof Contact) {
            return null;
        }

        return route('contacts.show', array_filter([
            'vault' => $this->vault,
            'contact' => $contact,
            'page' => $this->pageCarryingTheField($contact),
        ]));
    }

    private function pageCarryingTheField(Contact $contact): ?string
    {
        $placement = StructureVersionSection::query()
            ->where('account_id', $this->reminder->account_id)
            ->where('structure_version_id', $contact->structure_version_id)
            ->where('section_version_id', $this->reminder->field?->section_version_id)
            ->first();

        return $placement?->structureVersionPage->key;
    }

    /** @return array{title: string, body: string}|null */
    public function problem(): ?array
    {
        if ($this->reminder->is_active) {
            return null;
        }

        return match ($this->reminder->inactive_reason) {
            ReminderInactiveReasonEnum::ValueCleared => [
                'title' => __('This reminder has no date to follow'),
                'body' => __('The date it read is gone. Fill it in again on the record and a new reminder can be built from it.'),
            ],
            ReminderInactiveReasonEnum::RecordArchived => [
                'title' => __('Nothing is sent while the record is archived'),
                'body' => __('The record it is about is archived.'),
            ],
            ReminderInactiveReasonEnum::RecordDeleted => [
                'title' => __('Nothing is sent while the record is deleted'),
                'body' => __('The record it is about was deleted.'),
            ],
            ReminderInactiveReasonEnum::FieldRemoved => [
                'title' => __('This reminder has no field to read'),
                'body' => __('The field it reads has left the structure. Put the field back and this reminder starts again on its own.'),
            ],
            null => [
                'title' => __('This reminder is stopped'),
                'body' => __('It is stopped.'),
            ],
        };
    }

    /** @return list<array{date: string, row: ReminderRow, category: ReminderOccurrenceCategoryEnum}> */
    public function awaiting(): array
    {
        if (! $this->reminder->is_active) {
            return [];
        }

        $today = CarbonImmutable::now($this->user->timezone);

        $rows = $this->reminder->occurrences()
            ->active()
            ->orderBy('local_date')
            ->get()
            ->map(fn (ReminderOccurrence $occurrence): array => $this->awaitingEntry($occurrence, $today));

        // Deadlines are produced shortly before they fall due, so the next date is shown before it exists.
        $hasUpcoming = $rows->contains(fn (array $row): bool => $row['category'] === ReminderOccurrenceCategoryEnum::Upcoming);
        $next = $hasUpcoming ? null : NextReminderDeadlines::for(new Collection([$this->reminder]), $today->startOfDay())->first();

        if ($next !== null) {
            $rows->push($this->awaitingEntry($next, $today));
        }

        return $rows->values()->all();
    }

    /** @return array{date: string, row: ReminderRow, category: ReminderOccurrenceCategoryEnum} */
    private function awaitingEntry(ReminderOccurrence $occurrence, CarbonImmutable $today): array
    {
        return [
            'date' => DateFormatEnum::write($occurrence->local_date->locale(app()->getLocale()), $this->dateFormat()),
            'row' => $this->rowFor($occurrence),
            'category' => ReminderOccurrenceCategoryEnum::for($occurrence, $today),
        ];
    }

    /** @return Paginator<int, ReminderOccurrence> */
    public function history(): Paginator
    {
        $tomorrow = CarbonImmutable::now($this->user->timezone)->addDay()->toDateString();

        return once(fn (): Paginator => $this->reminder->occurrences()
            ->where(function (Builder $query) use ($tomorrow): void {
                $query
                    ->whereNotNull('answer')
                    ->orWhere(function (Builder $cancelled) use ($tomorrow): void {
                        $cancelled->whereNotNull('cancelled_at')->where('local_date', '<', $tomorrow);
                    });
            })
            ->orderByDesc('local_date')
            ->simplePaginate(self::HISTORY_PER_PAGE));
    }

    /** @return list<array{date: string, outcome: string, answered: string|null}> */
    public function historyRows(): array
    {
        return array_values(array_map(fn (ReminderOccurrence $occurrence): array => [
            'date' => DateFormatEnum::write($occurrence->local_date->locale(app()->getLocale()), $this->dateFormat()),
            'outcome' => $this->rowFor($occurrence)->outcome(),
            'answered' => $this->answeredLine($occurrence),
        ], $this->history()->items()));
    }

    private function answeredLine(ReminderOccurrence $occurrence): ?string
    {
        if ($occurrence->answered_at === null) {
            return null;
        }

        $at = $occurrence->answered_at->setTimezone($this->user->timezone)->locale(app()->getLocale());

        return __('Answered :date at :time', [
            'date' => DateFormatEnum::write($at, $this->dateFormat()),
            'time' => $this->user->time_format->format($at),
        ]);
    }

    public function deleteLine(): string
    {
        $answers = $this->reminder->occurrences()->answered()->count();

        if ($answers === 0) {
            return __('This removes “:name” and its upcoming dates. The record it is about stays as it is.', ['name' => $this->title()]);
        }

        return trans_choice(
            'This removes “:name” and its upcoming dates. The :count answer you gave is kept, and the record it is about stays as it is.|This removes “:name” and its upcoming dates. The :count answers you gave are kept, and the record it is about stays as it is.',
            $answers,
            ['name' => $this->title()],
        );
    }

    public function deliveryPage(): int
    {
        return $this->deliveryPage;
    }

    /** @return Paginator<int, array{date: string, time: string, title: string, note: string, tone: string}> */
    public function deliveryLog(): Paginator
    {
        $history = new ReminderHistory(user: $this->user, reminder: $this->reminder, page: $this->deliveryPage);

        return new Paginator($history->get(), ReminderHistory::LIMIT, $this->deliveryPage, [
            'path' => route('reminders.show', ['vault' => $this->vault, 'reminder' => $this->reminder]),
            'pageName' => 'deliveryPage',
        ])->hasMorePagesWhen($history->hasMore())->withQueryString();
    }

    private function rowFor(ReminderOccurrence $occurrence): ReminderRow
    {
        return new ReminderRow(
            user: $this->user,
            occurrence: $occurrence
                ->setRelation('reminder', $this->reminder)
                ->setRelation('vault', $this->vault),
        );
    }

    public function deleteUrl(): string
    {
        return route('reminders.destroy', ['vault' => $this->vault, 'reminder' => $this->reminder]);
    }

    private function dateFormat(): string
    {
        return $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];
    }
}
