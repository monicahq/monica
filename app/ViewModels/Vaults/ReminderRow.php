<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\DefaultFieldEnum;
use App\Enums\NotificationChannelEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Enums\ReminderPostponePresetEnum;
use App\Models\Contact;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * One line of the reminders list.
 *
 * It sits beside the two view models rather than inside either, because both
 * tabs draw a person, a date and a sentence, and a row that read differently
 * from one tab to the other would be two answers to one question.
 *
 * A row is a deadline and not a rule. Three of the four things somebody can do
 * to it act on one concrete instant, so that instant is what the row has to be,
 * and a rule with nothing generated has no row at all.
 *
 * The instant shown is the one the deadline is due at now, never the one it was
 * generated for. Somebody who put a reminder off wants to see where they moved
 * it to; the original instant is history and stays on the record.
 *
 * Two readings of the same instant are offered on purpose. The words say how far
 * off it is, which is what somebody scanning the list wants, and the exact line
 * says the instant with its zone and offset, which is what somebody who distrusts
 * the words wants. The exact line names the rule's zone rather than the reader's,
 * because that is the zone the reminder actually fires in.
 */
class ReminderRow
{
    public function __construct(
        private readonly User $user,
        private readonly ReminderOccurrence $occurrence,
    ) {}

    /**
     * @return array{uuid: string, day: string, month: string, year: string, cadence: string, cadenceIcon: string, contactName: string|null, contactUrl: string|null, avatarUrl: string|null, text: string, icon: string, relative: string, exact: string, isLate: bool, zone: string, currently: string, dismissUrl: string, testUrl: string, postponeUrl: string, presets: array<int, array{value: string, label: string, hint: string}>}
     */
    public function get(): array
    {
        $contact = $this->contact();

        return [
            'uuid' => $this->occurrence->uuid,
            'zone' => $this->zone(),
            'dismissUrl' => route('reminders.occurrences.dismissal.update', [
                'vault' => $this->occurrence->vault_id,
                'reminderOccurrence' => $this->occurrence,
            ]),
            'testUrl' => route('reminders.occurrences.testNotification.create', [
                'vault' => $this->occurrence->vault_id,
                'reminderOccurrence' => $this->occurrence,
            ]),
            'postponeUrl' => route('reminders.occurrences.postponement.update', [
                'vault' => $this->occurrence->vault_id,
                'reminderOccurrence' => $this->occurrence,
            ]),
            'presets' => $this->presets(),
            'currently' => $this->currently(),
            'day' => $this->dueAt()->translatedFormat('j'),
            'month' => $this->dueAt()->translatedFormat('M'),
            'year' => $this->dueAt()->translatedFormat('Y'),
            'cadence' => $this->cadence(),
            'cadenceIcon' => $this->cadenceIcon(),
            'contactName' => $contact?->name,
            'contactUrl' => $this->contactUrl($contact),
            'avatarUrl' => $this->avatarUrl($contact),
            'text' => $this->text($contact),
            'icon' => $this->icon(),
            'relative' => $this->relative(),
            'exact' => $this->exact(),
            'isLate' => $this->isLate(),
        ];
    }

    /**
     * The same deadline seen from the Sent tab, which asks different questions
     * of it: when it actually went out rather than when it was due, what it went
     * out on, and what became of it afterwards.
     *
     * It lives here rather than in a second row object because it is one
     * occurrence read two ways, and the five facts about the record it is
     * about are the same five either way.
     *
     * @return array{uuid: string, relative: string, exact: string, iso: string, channels: array<int, string>, contactName: string|null, contactUrl: string|null, avatarUrl: string|null, text: string, icon: string, outcome: string, tone: string}
     */
    public function sent(): array
    {
        $contact = $this->contact();
        $at = $this->sentAt();

        return [
            'uuid' => $this->occurrence->uuid,
            'relative' => $at->diffForHumans(),
            'exact' => __('Sent :date at :time :abbreviation (:zone, UTC:offset)', $this->partsOf($at)),
            'iso' => $at->toIso8601String(),
            'channels' => $this->channels(),
            'contactName' => $contact?->name,
            'contactUrl' => $this->contactUrl($contact),
            'avatarUrl' => $this->avatarUrl($contact),
            'text' => $this->text($contact),
            'icon' => $this->icon(),
            'outcome' => $this->outcome(),
            'tone' => $this->tone(),
        ];
    }

    /**
     * When it went out, read in the rule's own zone like every other instant on
     * these screens.
     *
     * A row only reaches the Sent tab because `notified_at` is set, so the
     * fallback is unreachable and is there to keep the type honest rather than
     * to describe a case.
     */
    private function sentAt(): CarbonImmutable
    {
        return ($this->occurrence->notified_at ?? $this->occurrence->scheduled_for)
            ->setTimezone($this->zone())
            ->locale(app()->getLocale());
    }

    /**
     * What it actually went out on, one word per channel, taken from the
     * notifications written for this deadline rather than from what the account
     * has switched on: the question is what happened, not what was allowed.
     *
     * The wording is here rather than on the enum because this is the only
     * screen that says a channel out loud.
     *
     * They are ordered by the enum rather than by whichever notification was
     * written first, so two rows that went out the same two ways read the same
     * way round. Nothing decides the order of the rows underneath, and a mark
     * that moves between rows is a mark somebody has to read twice.
     *
     * @return array<int, string>
     */
    private function channels(): array
    {
        $sent = $this->occurrence->notifications->pluck('channel');

        $words = [];

        foreach (NotificationChannelEnum::cases() as $channel) {
            if (! $sent->contains($channel)) {
                continue;
            }

            $words[] = match ($channel) {
                NotificationChannelEnum::InApp => __('In the app'),
                NotificationChannelEnum::Email => __('By email'),
                NotificationChannelEnum::Push => __('Pushed'),
            };
        }

        return $words;
    }

    /**
     * What became of the deadline after it was sent.
     *
     * Three states and no fourth. The product has no notion of a reminder being
     * completed, and a word here claiming otherwise would be inventing a state
     * nothing can produce, so a deadline nobody has touched is still open rather
     * than done.
     */
    private function outcome(): string
    {
        return match ($this->occurrence->status) {
            ReminderOccurrenceStatusEnum::Dismissed => __('Dismissed'),
            ReminderOccurrenceStatusEnum::Postponed => __('Put off'),
            default => __('Still open'),
        };
    }

    private function tone(): string
    {
        return match ($this->occurrence->status) {
            ReminderOccurrenceStatusEnum::Dismissed => 'neutral',
            ReminderOccurrenceStatusEnum::Postponed => 'info',
            default => 'warning',
        };
    }

    /**
     * Where the deadline stands now, for the dialog that is about to move it.
     *
     * It names the day and the hour and stops there. The zone is said once,
     * under the fields, rather than twice.
     */
    private function currently(): string
    {
        $due = $this->dueAt();

        return __('Currently set for :date at :time', [
            'date' => DateFormatEnum::write($due, $this->dateFormat()),
            'time' => $this->user->time_format->format($due),
        ]);
    }

    /**
     * The ready answers to "remind me later", each with the instant it lands on.
     *
     * The instants are worked out here rather than in the browser, because they
     * depend on the zone frozen on the rule and on the rule's own hour. A
     * dialog that computed them from the reader's clock would name an instant
     * the engine does not hold.
     *
     * @return array<int, array{value: string, label: string, hint: string}>
     */
    private function presets(): array
    {
        $rule = $this->occurrence->reminder;

        if ($rule === null) {
            return [];
        }

        $presets = [];

        foreach (ReminderPostponePresetEnum::cases() as $preset) {
            $at = $preset->resolve($rule)->setTimezone($this->zone())->locale(app()->getLocale());

            $presets[] = [
                'value' => $preset->value,
                'label' => __($preset->label()),
                'hint' => __(':date at :time', [
                    'date' => DateFormatEnum::write($at, $this->dateFormat()),
                    'time' => $this->user->time_format->format($at),
                ]),
            ];
        }

        return $presets;
    }

    /**
     * Whether the instant has passed and nobody has dealt with it.
     *
     * It is what the Overdue bucket is made of, and the row says it in words as
     * well as in colour: a reader who cannot tell the warning tint from the
     * ordinary one still reads "4 days ago" and "Sent".
     */
    public function isLate(): bool
    {
        return $this->occurrence->scheduled_for->isPast();
    }

    /**
     * The instant the deadline is due at, read in the zone frozen on its rule.
     *
     * The bucket a row falls in is decided from this, and it does not matter that
     * the zone is the rule's rather than the reader's: comparing two instants is
     * the same comparison whatever zone either is written in.
     */
    public function dueAt(): CarbonImmutable
    {
        return $this->occurrence->scheduled_for
            ->setTimezone($this->zone())
            ->locale(app()->getLocale());
    }

    private function zone(): string
    {
        return $this->occurrence->reminder->timezone ?? $this->user->timezone;
    }

    /**
     * Who the reminder is about, for the grouping that puts them together.
     */
    public function contactName(): ?string
    {
        return $this->contact()?->name;
    }

    private function contact(): ?Contact
    {
        $record = $this->occurrence->reminder?->remindable;

        return $record instanceof Contact ? $record : null;
    }

    private function contactUrl(?Contact $contact): ?string
    {
        if ($contact === null) {
            return null;
        }

        return route('contacts.show', ['vault' => $this->occurrence->vault_id, 'contact' => $contact]);
    }

    /**
     * The file itself rather than a public address: a picture of somebody is as
     * private as the record it sits on.
     */
    private function avatarUrl(?Contact $contact): ?string
    {
        if ($contact?->avatar === null) {
            return null;
        }

        return route('files.show', ['vault' => $this->occurrence->vault_id, 'file' => $contact->avatar]);
    }

    /**
     * What the reminder says. A rule always carries a name, and the record's own
     * name is the fallback for a row whose rule has gone.
     */
    private function text(?Contact $contact): string
    {
        $name = $this->occurrence->reminder?->name;

        if ($name !== null && mb_trim($name) !== '') {
            return $name;
        }

        return $contact->name ?? __('A reminder');
    }

    /**
     * How often the rule comes round, said as a standalone chip rather than as
     * the fragment the panel on a contact puts inside a sentence.
     */
    private function cadence(): string
    {
        $rule = $this->occurrence->reminder;

        if ($rule === null) {
            return '';
        }

        $interval = $rule->frequency_interval;

        /* The wordings below pin one of themselves to a single number, and a
           pinned wording must never be reached at zero: French groups zero with
           the singular, so "Every year" is what a French reader would see for a
           rule spaced out by no years at all. The column defaults to one and the
           form refuses less, so this is a backstop and not a case somebody can
           produce. */
        if ($interval === 0) {
            return '';
        }

        return match ($rule->cadence) {
            ReminderCadenceEnum::Once => __('One time'),
            ReminderCadenceEnum::Weekly => trans_choice('{1}Every week|Every :count weeks', $interval),
            ReminderCadenceEnum::Monthly => trans_choice('{1}Every month|Every :count months', $interval),
            ReminderCadenceEnum::Yearly => trans_choice('{1}Every year|Every :count years', $interval),
        };
    }

    private function cadenceIcon(): string
    {
        return $this->occurrence->reminder?->cadence->recurs() === true ? 'calendar-repeat' : 'calendar';
    }

    /**
     * The mark standing for the kind of date the rule reads.
     *
     * A field carries no icon, and it should not: which glyph suits a date is a
     * presentational choice and this is the only screen making it. Only one of
     * the fields the product ships is a date, so everything a user invented
     * falls back to a calendar rather than being guessed at.
     */
    private function icon(): string
    {
        $key = $this->occurrence->reminder?->field?->key;

        return $key === DefaultFieldEnum::Birthday->value ? 'gift' : 'calendar';
    }

    private function relative(): string
    {
        return $this->dueAt()->diffForHumans();
    }

    /**
     * The instant in full, for somebody who wants to check the words.
     *
     * The zone and the offset are both named because neither answers the
     * question alone: an abbreviation is ambiguous across the world and an
     * offset says nothing about which place chose it.
     */
    private function exact(): string
    {
        $when = $this->partsOf($this->dueAt());

        if ($this->isLate()) {
            return __('Sent :date at :time :abbreviation (:zone, UTC:offset)', $when);
        }

        return __('Will be sent :date at :time :abbreviation (:zone, UTC:offset)', $when);
    }

    /**
     * One instant taken apart for the sentence that names it.
     *
     * @return array<string, string>
     */
    private function partsOf(CarbonImmutable $moment): array
    {
        return [
            'date' => DateFormatEnum::write($moment, $this->dateFormat()),
            'time' => $this->user->time_format->format($moment),
            'abbreviation' => $moment->format('T'),
            'zone' => $this->zone(),
            'offset' => $moment->format('P'),
        ];
    }

    private function dateFormat(): string
    {
        return $this->user->date_format->value
            ?? (string) config('monica.locales.'.app()->getLocale().'.date_format', DateFormatEnum::MonthSpelled->value);
    }
}
