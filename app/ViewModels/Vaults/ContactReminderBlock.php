<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;
use App\Helpers\ReminderSchedule;
use App\Helpers\TimezoneCatalogue;
use App\Models\Contact;
use App\Models\Field;
use App\Models\Reminder;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * What the reminder panel under one date needs in order to be drawn.
 *
 * It sits beside {@see ContactViewModel} rather than inside it, for the reason a
 * rich page keeps its blocks in small objects: this is one region of one control
 * of one section, and the screen's own view model has a page to assemble.
 *
 * A date with no rule still gets everything. The panel is drawn whether the box
 * is ticked or not, so the defaults have to be real: a rule nobody has set up yet
 * reads as one moment on the day, coming round every year, at nine in the
 * morning, in the zone the person's preferences carry.
 *
 * The sentence at the foot is assembled here and again in the browser, out of the
 * same translated fragments. That is deliberate and it is the only duplication in
 * the panel: what somebody is typing has to be described as they type it, and
 * what the page arrives holding has to be described before any of it runs.
 *
 * The one thing the browser is not asked to work out is the next date. It needs a
 * calendar rather than string assembly, so it describes the rule as saved and
 * says so.
 */
class ContactReminderBlock
{
    public function __construct(
        private readonly User $user,
        private readonly Contact $contact,
        private readonly Field $field,
        private readonly ?Reminder $reminder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $rule = $this->reminder;

        return [
            'isOn' => $rule instanceof Reminder,
            'name' => $rule?->name,
            'placeholder' => $this->placeholder(),
            'recurs' => $rule === null || $rule->cadence->recurs(),
            'cadence' => $this->cadence($rule),
            'interval' => $rule instanceof Reminder ? $rule->frequency_interval : 1,
            'time' => $this->time($rule),
            'timezone' => $this->zone($rule),
            'zones' => $this->zones(),
            'zoneNote' => $this->zoneNote($rule),
            'moments' => $this->moments($rule),
            'summary' => $this->summary($rule),
            'nextLine' => $this->nextLine($rule),
            'hint' => $rule instanceof Reminder
                ? __('It reads from this date and nothing else.')
                : __('Be told about this date, ahead of time, in your own time zone.'),
            'quietBecause' => $this->quietBecause($rule),
            'words' => $this->words(),
            'errorKey' => "reminders.{$this->field->id}",
        ];
    }

    /**
     * What the mark beside the date says when the page is being read rather
     * than edited, or null for a date that carries no mark.
     *
     * It is the reading state's whole account of the reminder, so it is asked
     * for on its own and not out of {@see get()}: the panel that explains a rule
     * is behind the Edit button, and a date that says nothing looks exactly like
     * a date nobody set anything on.
     *
     * It says the sentence the panel says rather than the words "reminder set",
     * because the glyph is never the only carrier of the meaning and the
     * sentence is what somebody actually wants to know.
     *
     * A rule that has gone quiet gets no mark. It is not firing, and a mark
     * beside its date would say the opposite of what the panel explains.
     */
    public function mark(): ?string
    {
        $rule = $this->reminder;

        if ($rule === null || ! $rule->is_active) {
            return null;
        }

        return $this->summary($rule);
    }

    /**
     * The words the browser assembles the same sentences from while somebody is
     * typing, with `:n` where the number goes.
     *
     * They are built here rather than in the panel so the two assemblers read
     * the same strings. Each pair is asked for at one and at two, which is a
     * literal number choosing a wording rather than a count compared to one, and
     * it is enough for every locale this product ships in: none of the nine has
     * more than two plural categories, and no amount here is ever zero.
     *
     * @return array<string, mixed>
     */
    private function words(): array
    {
        return [
            'units' => [
                ReminderMomentUnitEnum::Days->value => $this->pair(':count day|:count days'),
                ReminderMomentUnitEnum::Weeks->value => $this->pair(':count week|:count weeks'),
                ReminderMomentUnitEnum::Months->value => $this->pair(':count month|:count months'),
            ],
            'intervals' => [
                ReminderCadenceEnum::Weekly->value => $this->pair('{1}every week|every :count weeks'),
                ReminderCadenceEnum::Monthly->value => $this->pair('{1}every month|every :count months'),
                ReminderCadenceEnum::Yearly->value => $this->pair('{1}every year|every :count years'),
            ],
            'onTheDay' => __('on the day'),
            'before' => __(':span before'),
            'after' => __(':span after'),
            'and' => __('and'),
            'once' => __('once and no more'),
            'summary' => __('You will be reminded :moments, :cadence, at :time :zone.'),
            'nothing' => __('Choose at least one moment to be told about.'),
        ];
    }

    /**
     * One counted phrase in both of its wordings, with `:n` left where the
     * number goes.
     *
     * @return array{one: string, other: string}
     */
    private function pair(string $phrase): array
    {
        return [
            'one' => trans_choice($phrase, 1, ['count' => ':n']),
            'other' => trans_choice($phrase, 2, ['count' => ':n']),
        ];
    }

    /**
     * What the reminder is called when nobody words it, which is what the field
     * and the record already say between them.
     */
    private function placeholder(): string
    {
        return __(':label — :name', ['label' => $this->field->label, 'name' => $this->contact->name]);
    }

    /**
     * The cadence the select opens on. A rule that fires once still has to leave
     * the select on something, and a yearly date is what a date on a record
     * almost always is.
     */
    private function cadence(?Reminder $rule): string
    {
        if ($rule === null || ! $rule->cadence->recurs()) {
            return ReminderCadenceEnum::Yearly->value;
        }

        return $rule->cadence->value;
    }

    /**
     * The zone the panel shows: the one frozen on the rule, and otherwise the
     * one the person's preferences carry.
     */
    private function zone(?Reminder $rule): string
    {
        return $rule instanceof Reminder ? $rule->timezone : $this->user->timezone;
    }

    private function time(?Reminder $rule): string
    {
        if ($rule === null) {
            return Reminder::DEFAULT_TIME;
        }

        return mb_substr((string) $rule->time_of_day, 0, 5);
    }

    /**
     * Every zone the product knows, described in the language being read. It is
     * the list the preferences screen searches, offered here as a plain menu
     * because a reminder is set once and the zone is nearly always the one
     * already filled in.
     *
     * @return list<array{timezone: string, label: string}>
     */
    private function zones(): array
    {
        return array_map(fn (array $zone): array => [
            'timezone' => $zone['timezone'],
            'label' => $zone['country'] === '' ? $zone['city'] : "{$zone['city']}, {$zone['country']}",
        ], TimezoneCatalogue::all());
    }

    private function zoneNote(?Reminder $rule): string
    {
        if ($rule === null) {
            return __('Filled in from your preferences. It is stored with the reminder, so moving away later does not shift it.');
        }

        return __('Frozen when the reminder was made. Changing your preferences does not shift it.');
    }

    /**
     * The moments the rule fires at, each with the words that describe it. A
     * rule nobody has set up yet has one, on the day itself, which is the moment
     * somebody who ticks the box and saves would have meant.
     *
     * @return list<array{direction: string, amount: int|null, unit: string|null, phrase: string}>
     */
    private function moments(?Reminder $rule): array
    {
        if ($rule === null) {
            return [[
                'direction' => ReminderMomentDirectionEnum::On->value,
                'amount' => null,
                'unit' => null,
                'phrase' => ReminderWords::moment(ReminderMomentDirectionEnum::On, null, null),
            ]];
        }

        return array_map(fn (array $moment): array => [
            'direction' => $moment['direction']->value,
            'amount' => $moment['amount'],
            'unit' => $moment['unit']?->value,
            'phrase' => ReminderWords::moment($moment['direction'], $moment['amount'], $moment['unit']),
        ], ReminderSchedule::momentDates($rule));
    }

    private function summary(?Reminder $rule): string
    {
        $said = array_column($this->moments($rule), 'phrase');

        if ($said === []) {
            return __('Choose at least one moment to be told about.');
        }

        $listed = count($said) === 1
            ? $said[0]
            : implode(', ', array_slice($said, 0, -1)).' '.__('and').' '.end($said);

        return __('You will be reminded :moments, :cadence, at :time :zone.', [
            'moments' => $listed,
            'cadence' => ReminderWords::cadence(
                ReminderCadenceEnum::from($this->cadence($rule)),
                $rule instanceof Reminder ? $rule->frequency_interval : 1,
            ),
            'time' => $this->time($rule),
            'zone' => $this->zone($rule),
        ]);
    }

    /**
     * The day the rule falls on next, said only once there is a rule to say it
     * about. A rule nobody has saved yet has no next day, and guessing one from
     * controls nobody has submitted would be describing something that does not
     * exist.
     */
    private function nextLine(?Reminder $rule): ?string
    {
        if ($rule === null) {
            return null;
        }

        $next = ReminderSchedule::nextDate($rule);

        if (! $next instanceof CarbonImmutable) {
            return __('It has nothing ahead of it any more.');
        }

        return __('Next: :date.', ['date' => $next->isoFormat('LL')]);
    }

    /**
     * Why the rule has gone quiet, in words. Nothing about a reminder disappears
     * without an explanation, and a rule still firing has nothing to explain.
     */
    private function quietBecause(?Reminder $rule): ?string
    {
        if ($rule === null || $rule->is_active) {
            return null;
        }

        return match ($rule->inactive_reason) {
            ReminderInactiveReasonEnum::ValueCleared => __('This reminder is quiet because the date it read is gone. Fill the date in again to start a new one.'),
            ReminderInactiveReasonEnum::RecordArchived => __('This reminder is quiet because this record is archived. Unarchive it and the reminder starts again.'),
            ReminderInactiveReasonEnum::RecordDeleted => __('This reminder is quiet because this record was deleted. Restore it and the reminder starts again.'),
            ReminderInactiveReasonEnum::FieldRemoved => __('This reminder is quiet because this field has left the structure. Put the field back and the reminder starts again.'),
            ReminderInactiveReasonEnum::PausedByUser => __('This reminder is quiet because you paused it. Open its own screen to start it again.'),
            null => __('This reminder is quiet.'),
        };
    }
}
