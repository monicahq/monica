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

/**
 * One reminder, explained.
 *
 * The screen exists because a reminder was only ever visible as a checkbox under
 * a date on a record, and as deadlines on a list. Neither says what the rule is,
 * and a rule somebody cannot read is a rule they cannot trust.
 *
 * It is the rule that is drawn here, not a deadline, which is the opposite of
 * the list beside it: the list is deadlines because three of the four things it
 * offers act on one instant, and this page is the rule because everything it
 * explains belongs to the rule. The deadlines still appear, in their own panel,
 * and the row they are drawn as is the list's, so a deadline reads the same way
 * in both places.
 *
 * Two blocks are their own objects, {@see ReminderHistory} and
 * {@see ReminderDelivery}, because a rich page keeps its panels in small objects
 * beside it. Everything else is here.
 *
 * Every instant is read in the zone frozen on the rule and says so. That zone is
 * not necessarily the reader's, and a page that quietly translated into the
 * reader's would promise an hour the engine does not hold.
 *
 * Nothing here writes. Pausing, resuming and deleting are actions the controllers
 * call; this only says where those live and whether they are on offer.
 */
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

    /**
     * Where the rule is read from, said as a sentence rather than as a field and
     * a date side by side: what somebody wants to know is that this reminder
     * follows that date, and will follow it wherever it moves.
     */
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
     * Whether the rule is firing, and if not, why.
     *
     * `canResume` is the whole reason the reason is kept. Only what somebody
     * chose is theirs to undo: a rule quiet because its record is archived comes
     * back by unarchiving the record, and offering to reactivate it here would
     * offer something this screen cannot do.
     *
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

    /**
     * Why the rule has gone quiet, in words. Nothing about a reminder disappears
     * without an explanation, and this is where the explanation is read.
     */
    private function quietBecause(): string
    {
        return match ($this->reminder->inactive_reason) {
            ReminderInactiveReasonEnum::ValueCleared => __('The date it read is gone. Fill it in again on the record and a new reminder can be built from it.'),
            ReminderInactiveReasonEnum::RecordArchived => __('The record it is about is archived. Unarchive it and this reminder starts again on its own.'),
            ReminderInactiveReasonEnum::RecordDeleted => __('The record it is about was deleted. Restore it and this reminder starts again on its own.'),
            ReminderInactiveReasonEnum::FieldRemoved => __('The field it reads has left the structure. Put the field back and this reminder starts again on its own.'),
            ReminderInactiveReasonEnum::PausedByUser => __('You paused it. Nothing is delivered while it is paused, and the schedule is kept exactly as it was.'),
            null => __('It is not firing.'),
        };
    }

    /**
     * What happens next, or what is not happening.
     *
     * A paused rule keeps its schedule, so the sentence here is about what
     * resuming would restore rather than about a date, which is what makes
     * pausing legible as something reversible.
     *
     * @return array{isScheduled: bool, overline: string, date: string, note: string, tone: string}
     */
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
                'note' => __('This reminder fires once, and its date has passed.'),
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
     * The rule in four facts and its moments.
     *
     * The four are the four somebody checks when a reminder surprises them: how
     * often, off which date, at what hour, and in whose zone. The zone is the one
     * that surprises people, so it says whose it is.
     *
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
                    'note' => __('The hour the reminder fires at.'),
                ],
                [
                    'label' => __('Time zone'),
                    'value' => $this->reminder->timezone,
                    'note' => $this->reminder->timezone === $this->user->timezone
                        ? __('Frozen when the reminder was made, and the same as yours.')
                        : __('Frozen when the reminder was made. Yours is :zone.', ['zone' => $this->user->timezone]),
                ],
            ],
            'moments' => $this->moments(),
        ];
    }

    /**
     * Every moment the rule fires at, with the day each falls on next.
     *
     * @return list<array{label: string, date: string|null}>
     */
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

    /**
     * @return list<array{date: string, time: string, title: string, note: string, tone: string}>
     */
    public function history(): array
    {
        return new ReminderHistory(user: $this->user, reminder: $this->reminder)->get();
    }

    /**
     * The deadlines still ahead, drawn as the list draws them so that one
     * deadline reads the same way in both places.
     *
     * A rule that is not firing produces nothing, and its deadlines are cancelled
     * anyway. Asking after the rule as well is what keeps the screen right in the
     * moment between the two, which is the same guard
     * {@see ReminderOccurrence::scopeUpcomingInVault()} carries for the list.
     *
     * @return list<array<string, mixed>>
     */
    public function occurrences(): array
    {
        if (! $this->reminder->is_active) {
            return [];
        }

        return $this->reminder->occurrences()
            ->whereIn('status', ReminderOccurrenceStatusEnum::actionable())
            ->orderBy('scheduled_for')
            ->get()
            ->map(fn (ReminderOccurrence $occurrence): array => new ReminderRow(
                user: $this->user,
                occurrence: $occurrence
                    ->setRelation('reminder', $this->reminder)
                    ->setRelation('vault', $this->vault),
            )->get())
            ->all();
    }

    /**
     * What the panel of deadlines says when it holds none, which is two different
     * things: a rule that is firing has simply not reached far enough ahead yet,
     * and a rule that is quiet is not going to.
     */
    public function stillToComeEmptyLine(): string
    {
        if (! $this->reminder->is_active) {
            return __('Nothing is scheduled while this reminder is not firing. Its schedule is kept, so what was ahead comes back with it.');
        }

        return __('Deadlines are produced a while before they fall due, so a yearly reminder sits empty here for most of the year. The schedule above is what it will do.');
    }

    /**
     * The record the reminder is about, or null for a rule about something that
     * has no screen yet.
     *
     * Three facts and no more. The design asked for a group, a place and the last
     * conversation, and this product has none of the three, so the card carries
     * what is true instead of what was drawn.
     *
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

    /**
     * Where the rule is changed, which is the block under the date it reads and
     * not this screen.
     *
     * A reminder is written by the save of the record's own block, so a second
     * editing surface here would be two places to change one rule. Landing on the
     * page carrying that field is as close as a link can get.
     */
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

    /**
     * Which page of the record's template holds the section the field is in, or
     * null to land on the overview.
     *
     * It is asked of the record's own template version rather than of the field,
     * because a field belongs to a section and a section is placed on a page once
     * per template that carries it.
     */
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

    /**
     * The deadline the next notification will come from, which is the first one
     * still ahead that nobody has finished with.
     */
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
