<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Helpers\ReminderSchedule;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\Vault;

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
        $date = $this->reminder->fieldValue?->value_date;

        if ($date === null) {
            return __('Built from :label, which no longer holds a date.', [
                'label' => $this->reminder->field->label,
            ]);
        }

        return __('Built from :label, :date.', [
            'label' => $this->reminder->field->label,
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
     * @return array{isActive: bool, tone: string, icon: string, label: string, quietBecause: string|null, canResume: bool}
     */
    public function state(): array
    {
        $active = $this->reminder->is_active;

        return [
            'isActive' => $active,
            'tone' => $active ? 'success' : 'neutral',
            'icon' => $active ? 'check' : 'clock',
            'label' => $active ? __('Active') : __('Paused'),
            'quietBecause' => $active ? null : $this->quietBecause(),
            'canResume' => ! $active && ($this->reminder->inactive_reason?->isLiftedByUser() ?? false),
        ];
    }

    private function quietBecause(): string
    {
        return match ($this->reminder->inactive_reason) {
            ReminderInactiveReasonEnum::ValueCleared => __('The date it read is gone. Fill it in again on the record and a new reminder can be built from it.'),
            ReminderInactiveReasonEnum::RecordArchived => __('The record it is about is archived. Unarchive it and this reminder starts again on its own.'),
            ReminderInactiveReasonEnum::RecordDeleted => __('The record it is about was deleted. Restore it and this reminder starts again on its own.'),
            ReminderInactiveReasonEnum::FieldRemoved => __('The field it reads has left the structure. Put the field back and this reminder starts again on its own.'),
            ReminderInactiveReasonEnum::PausedByUser => __('You paused it. Nothing is delivered while it is paused, and the schedule is kept exactly as it was.'),
            null => __('It is stopped.'),
        };
    }

    /** @return array{isScheduled: bool, overline: string, date: string, note: string, tone: string} */
    public function next(): array
    {
        if (! $this->reminder->is_active) {
            return [
                'isScheduled' => false,
                'overline' => __('Nothing is scheduled'),
                'date' => $this->state()['label'],
                'note' => $this->quietBecause(),
                'tone' => 'neutral',
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
            ];
        }

        $at = $at->setTimezone($this->reminder->timezone)->locale(app()->getLocale());

        return [
            'isScheduled' => true,
            'overline' => $occurrence === null ? __('Next, once it is generated') : __('Next notification'),
            'date' => __(':date at :time', [
                'date' => DateFormatEnum::write($at, $this->dateFormat()),
                'time' => $this->user->time_format->format($at),
            ]),
            'note' => __(':zone · :relative', [
                'zone' => $this->reminder->timezone,
                'relative' => $at->diffForHumans(),
            ]),
            'tone' => 'success',
        ];
    }

    /**
     * @return array{fields: list<array{label: string, value: string, note: string}>, moments: list<array{label: string, date: string|null}>}
     */
    public function schedule(): array
    {
        $date = $this->reminder->fieldValue?->value_date;

        return [
            'fields' => [
                [
                    'label' => __('Repeats'),
                    'value' => ReminderWords::cadence($this->reminder->cadence, $this->reminder->frequency_interval),
                    'note' => __('For as long as the date stays on the record.'),
                ],
                [
                    'label' => __('Source date'),
                    'value' => $date === null
                        ? __('Gone')
                        : DateFormatEnum::write($date->locale(app()->getLocale()), $this->dateFormat()),
                    'note' => __(':label, on the record.', ['label' => $this->reminder->field->label]),
                ],
                [
                    'label' => __('Time'),
                    'value' => mb_substr($this->reminder->time_of_day, 0, 5),
                    'note' => __('The hour the reminder is sent at.'),
                ],
                [
                    'label' => __('Time zone'),
                    'value' => $this->reminder->timezone,
                    'note' => $this->reminder->timezone === $this->user->timezone
                        ? __('Fixed when the reminder was made, and the same as yours.')
                        : __('Fixed when the reminder was made. Yours is :zone.', ['zone' => $this->user->timezone]),
                ],
            ],
            'moments' => $this->moments(),
        ];
    }

    /** @return list<array{label: string, date: string|null}> */
    private function moments(): array
    {
        return array_map(fn (array $moment): array => [
            'label' => ReminderWords::moment($moment['direction'], $moment['amount'], $moment['unit']),
            'date' => $moment['date'] === null
                ? null
                : DateFormatEnum::write($moment['date']->locale(app()->getLocale()), $this->dateFormat()),
        ], ReminderSchedule::momentDates($this->reminder));
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
            ->whereIn('status', ReminderOccurrenceStatusEnum::actionable())
            ->orderBy('scheduled_for')
            ->get()
            ->map(fn (ReminderOccurrence $occurrence): ReminderRow => new ReminderRow(
                user: $this->user,
                occurrence: $occurrence
                    ->setRelation('reminder', $this->reminder)
                    ->setRelation('vault', $this->vault),
            ))
            ->all();
    }

    public function stillToComeEmptyLine(): string
    {
        if (! $this->reminder->is_active) {
            return __('Nothing is scheduled while this reminder is stopped. Its schedule is kept, so it starts again where it was.');
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

        return route('contacts.show', array_filter([
            'vault' => $this->vault,
            'contact' => $contact,
            'page' => $this->pageCarryingTheField($contact),
        ]));
    }

    private function pageCarryingTheField(Contact $contact): ?string
    {
        $placement = TemplateVersionSection::query()
            ->where('account_id', $this->reminder->account_id)
            ->where('template_version_id', $contact->template_version_id)
            ->where('section_version_id', $this->reminder->field->section_version_id)
            ->first();

        return $placement?->templateVersionPage->key;
    }

    public function pauseUrl(): string
    {
        return route('reminders.pause.update', ['vault' => $this->vault, 'reminder' => $this->reminder]);
    }

    public function resumeUrl(): string
    {
        return route('reminders.pause.destroy', ['vault' => $this->vault, 'reminder' => $this->reminder]);
    }

    public function deleteUrl(): string
    {
        return route('reminders.destroy', ['vault' => $this->vault, 'reminder' => $this->reminder]);
    }

    private function nextOccurrence(): ?ReminderOccurrence
    {
        return $this->reminder->occurrences()
            ->whereIn('status', ReminderOccurrenceStatusEnum::actionable())
            ->orderBy('scheduled_for')
            ->first();
    }

    private function dateFormat(): string
    {
        return $this->user->date_format->value
            ?? (string) config('monica.locales.'.app()->getLocale().'.date_format', DateFormatEnum::MonthSpelled->value);
    }
}
