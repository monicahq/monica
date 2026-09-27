<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderOccurrenceCategoryEnum;
use App\Enums\ReminderSourceEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\ReminderSchedule;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ReminderViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Reminder $reminder,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function reminder(): Reminder
    {
        return $this->reminder;
    }

    public function title(): string
    {
        return $this->reminder->name;
    }

    public function remindersUrl(): string
    {
        return route('reminders.index', $this->vault);
    }

    public function builtFrom(): string
    {
        if ($this->reminder->source === ReminderSourceEnum::Manual) {
            return __('Written about this contact, first on :date.', [
                'date' => $this->reminder->starts_on === null
                    ? ''
                    : DateFormatEnum::write($this->reminder->starts_on->locale(app()->getLocale()), $this->dateFormat()),
            ]);
        }

        $date = $this->reminder->fieldValue?->value_date;

        if ($date === null) {
            return __('Built from :label, which no longer holds a date.', [
                'label' => $this->reminder->field->label ?? '',
            ]);
        }

        return __('Built from :label, :date.', [
            'label' => $this->reminder->field->label ?? '',
            'date' => DateFormatEnum::write(
                $date->locale(app()->getLocale()),
                $this->dateFormat(),
            ),
        ]);
    }

    public function createdLine(): string
    {
        $at = $this->reminder->created_at
            ->setTimezone($this->reminder->timezone)
            ->locale(app()->getLocale());

        return __('Created on :date.', [
            'date' => DateFormatEnum::write($at, $this->dateFormat()),
        ]);
    }

    /**
     * @return array{isActive: bool, tone: string, icon: string, label: string, quietBecause: string|null}
     */
    public function state(): array
    {
        $active = $this->reminder->is_active;

        return [
            'isActive' => $active,
            'tone' => $active ? 'success' : 'neutral',
            'icon' => $active ? 'check' : 'clock',
            'label' => $active ? __('Active') : __('Stopped'),
            'quietBecause' => $active ? null : $this->quietBecause(),
        ];
    }

    private function quietBecause(): string
    {
        return match ($this->reminder->inactive_reason) {
            ReminderInactiveReasonEnum::ValueCleared => __('The date it read is gone. Fill it in again on the record and a new reminder can be built from it.'),
            ReminderInactiveReasonEnum::RecordArchived => __('The record it is about is archived. Unarchive it and this reminder starts again on its own.'),
            ReminderInactiveReasonEnum::RecordDeleted => __('The record it is about was deleted. Restore it and this reminder starts again on its own.'),
            ReminderInactiveReasonEnum::FieldRemoved => __('The field it reads has left the structure. Put the field back and this reminder starts again on its own.'),
            null => __('It is stopped.'),
        };
    }

    /** @return array{isScheduled: bool, overline: string, date: string, note: string, tone: string, row: ReminderRow|null} */
    public function next(): array
    {
        if (! $this->reminder->is_active) {
            return [
                'isScheduled' => false,
                'overline' => __('Nothing is scheduled'),
                'date' => $this->state()['label'],
                'note' => $this->quietBecause(),
                'tone' => 'neutral',
                'row' => null,
            ];
        }

        $occurrence = $this->nextOccurrence();
        $at = $occurrence->scheduled_for ?? ReminderSchedule::nextDate($this->reminder);

        if ($at === null) {
            return [
                'isScheduled' => false,
                'overline' => __('Nothing ahead of it'),
                'date' => __('It has already happened'),
                'note' => __('This reminder is sent once, and its date has passed.'),
                'tone' => 'neutral',
                'row' => null,
            ];
        }

        $at = $at->setTimezone($this->reminder->timezone)->locale(app()->getLocale());
        $category = $occurrence === null
            ? ReminderOccurrenceCategoryEnum::Upcoming
            : ReminderOccurrenceCategoryEnum::for($occurrence, CarbonImmutable::now($this->user->timezone));

        return [
            'isScheduled' => true,
            'overline' => match ($category) {
                ReminderOccurrenceCategoryEnum::Overdue => __('Overdue, waiting for your answer'),
                ReminderOccurrenceCategoryEnum::Today => __('Today'),
                ReminderOccurrenceCategoryEnum::Upcoming => __('Next'),
            },
            'date' => __(':date at :time', [
                'date' => DateFormatEnum::write($at, $this->dateFormat()),
                'time' => $this->user->time_format->format($at),
            ]),
            'note' => __(':zone · :relative', [
                'zone' => $this->reminder->timezone,
                'relative' => $at->diffForHumans(),
            ]),
            'tone' => $category === ReminderOccurrenceCategoryEnum::Overdue ? 'warning' : 'success',
            'row' => $occurrence === null ? null : $this->rowFor($occurrence),
        ];
    }

    /**
     * @return array{fields: list<array{label: string, value: string, note: string|null}>}
     */
    public function schedule(): array
    {
        $date = $this->reminder->source === ReminderSourceEnum::Manual
            ? $this->reminder->starts_on
            : $this->reminder->fieldValue?->value_date;

        return [
            'fields' => [
                [
                    'label' => __('Repeats'),
                    'value' => ReminderWords::cadence($this->reminder->cadence, $this->reminder->frequency_interval),
                    'note' => null,
                ],
                [
                    'label' => __('Source date'),
                    'value' => $date === null
                        ? __('Gone')
                        : DateFormatEnum::write($date->locale(app()->getLocale()), $this->dateFormat()),
                    'note' => null,
                ],
                [
                    'label' => __('Time'),
                    'value' => mb_substr($this->reminder->time_of_day, 0, 5),
                    'note' => null,
                ],
                [
                    'label' => __('Time zone'),
                    'value' => $this->reminder->timezone,
                    'note' => $this->reminder->timezone === $this->user->timezone
                        ? null
                        : __('Fixed when the reminder was made. Yours is :zone.', ['zone' => $this->user->timezone]),
                ],
            ],
        ];
    }

    /**
     * @return array{channels: string, channelsNote: string, isSilent: bool, says: string, saysNote: string, lastSent: string|null, lastSentNote: string}
     */
    public function delivery(): array
    {
        return new ReminderDelivery(user: $this->user, reminder: $this->reminder)->get();
    }

    /** @return list<array{date: string, time: string, title: string, note: string, tone: string}> */
    public function history(): array
    {
        return new ReminderHistory(user: $this->user, reminder: $this->reminder)->get();
    }

    /** @return list<ReminderRow> */
    public function occurrences(): array
    {
        if (! $this->reminder->is_active) {
            return [];
        }

        return $this->reminder->occurrences()
            ->active()
            ->orderBy('local_date')
            ->get()
            ->map(fn (ReminderOccurrence $occurrence): ReminderRow => $this->rowFor($occurrence))
            ->all();
    }

    /** @return list<array{date: string, outcome: string, tone: string, answeredOn: string|null}> */
    public function answers(): array
    {
        $today = CarbonImmutable::now($this->user->timezone)->toDateString();

        return $this->reminder->occurrences()
            ->where(function (Builder $query) use ($today): void {
                $query->whereDate('local_date', '<=', $today)->orWhereNotNull('answer');
            })
            ->orderByDesc('local_date')
            ->limit(50)
            ->get()
            ->map(function (ReminderOccurrence $occurrence): array {
                $row = $this->rowFor($occurrence);

                return [
                    'date' => DateFormatEnum::write($occurrence->local_date->locale(app()->getLocale()), $this->dateFormat()),
                    'outcome' => $row->outcome(),
                    'tone' => $row->tone(),
                    'answeredOn' => $occurrence->answered_at === null
                        ? null
                        : DateFormatEnum::write($occurrence->answered_at->setTimezone($this->user->timezone)->locale(app()->getLocale()), $this->dateFormat()),
                ];
            })
            ->all();
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

    public function stillToComeEmptyLine(): string
    {
        if (! $this->reminder->is_active) {
            return __('Nothing is scheduled while this reminder is stopped.');
        }

        return __('Deadlines are produced shortly before they fall due, so a yearly reminder is empty here for most of the year. The schedule above is what it will do.');
    }

    /**
     * @return array{name: string, url: string, avatarUrl: string|null, initials: string, reminderCount: int, reminderCountLine: string}|null
     */
    public function contact(): ?array
    {
        $contact = $this->reminder->remindable;

        if (! $contact instanceof Contact) {
            return null;
        }

        $count = $contact->reminders()->active()->count();

        return [
            'name' => $contact->name,
            'url' => route('contacts.show', ['vault' => $this->vault, 'contact' => $contact]),
            'avatarUrl' => $contact->avatar === null
                ? null
                : route('files.show', ['vault' => $this->vault, 'file' => $contact->avatar]),
            'initials' => $contact->initials,
            'reminderCount' => $count,
            'reminderCountLine' => trans_choice(
                ':count reminder about them|:count reminders about them',
                $count,
            ),
        ];
    }

    public function editUrl(): string
    {
        $contact = $this->reminder->remindable;

        if (! $contact instanceof Contact) {
            return $this->remindersUrl();
        }

        if ($this->reminder->source === ReminderSourceEnum::Manual) {
            return route('contacts.reminders.edit', ['vault' => $this->vault, 'contact' => $contact, 'reminder' => $this->reminder]);
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

    public function deleteUrl(): string
    {
        return route('reminders.destroy', ['vault' => $this->vault, 'reminder' => $this->reminder]);
    }

    private function nextOccurrence(): ?ReminderOccurrence
    {
        return $this->reminder->occurrences()
            ->active()
            ->orderBy('local_date')
            ->first();
    }

    private function dateFormat(): string
    {
        return $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];
    }
}
