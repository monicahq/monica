<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\FieldValueFormatter;
use App\Helpers\FlagState;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * The whole of a contact written out as one Markdown document, to be read without the product: by
 * the account holder, or by whatever assistant they hand it to. It says what the contact screen says,
 * in the same order, and leaves out the values of sensitive fields when the reader may not reach them.
 */
class ContactMarkdownViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly bool $reachesSensitiveFields = false,
    ) {}

    public function document(): string
    {
        $parts = [
            '# '.$this->line($this->contact->name),
            $this->line(__('Exported on :date from the vault :vault.', [
                'date' => DateFormatEnum::write(CarbonImmutable::now($this->user->timezone), $this->dateFormat()),
                'vault' => $this->vault->name,
            ])),
            $this->names(),
            ...$this->pages(),
            $this->relationships(),
            $this->lifeEvents(),
            $this->groups(),
            $this->reminders(),
            $this->loans(),
        ];

        return implode("\n\n", array_filter($parts, fn (string $part): bool => $part !== ''))."\n";
    }

    private function names(): string
    {
        $items = [];

        if (filled($this->contact->nickname)) {
            $items[] = $this->item(__('Nickname'), $this->contact->nickname);
        }

        $this->contact->nameRepresentations
            ->reject(fn (ContactNameRepresentation $representation): bool => $representation->id === $this->contact->display_name_representation_id)
            ->map(fn (ContactNameRepresentation $representation): string => $representation->displayText())
            ->reject(fn (string $text): bool => $text === '' || $text === $this->contact->name)
            ->each(function (string $text) use (&$items): void {
                $items[] = $this->item(__('Also written'), $text);
            });

        return $this->part(__('Names'), $items);
    }

    /** @return list<string> */
    private function pages(): array
    {
        $parts = [];

        foreach ($this->contact->structureVersion->pages as $page) {
            $parts[] = $this->page($page);
        }

        return $parts;
    }

    private function page(StructureVersionPage $page): string
    {
        $sections = $this->contact->structureVersion->assembly
            ->where('structure_version_page_id', $page->id)
            ->map(fn (StructureVersionSection $row): string => $this->section($row))
            ->filter(fn (string $section): bool => $section !== '')
            ->all();

        if ($sections === []) {
            return '';
        }

        return '## '.$this->line($page->label)."\n\n".implode("\n\n", $sections);
    }

    private function section(StructureVersionSection $row): string
    {
        $items = $row->sectionVersion->fields
            ->whereNull('parent_field_id')
            ->filter(fn (Field $field): bool => $field->type->storesValue() && $this->reaches($field))
            ->map(fn (Field $field): string => $field->type->isRepeater() ? $this->repeater($field) : $this->item($field->label, $this->valueOf($field)))
            ->filter(fn (string $item): bool => $item !== '')
            ->all();

        if ($items === []) {
            return '';
        }

        return '### '.$this->line($row->title_override ?? $row->sectionVersion->section->name)."\n\n".implode("\n", $items);
    }

    private function repeater(Field $field): string
    {
        $children = $field->children
            ->filter(fn (Field $child): bool => $child->type->storesValue() && $this->reaches($child));

        $entries = $this->rows()
            ->get($field->id, collect())
            ->map(fn (FieldValueRow $row): string => $this->rowLine($row, $children))
            ->filter(fn (string $entry): bool => $entry !== '')
            ->map(fn (string $entry): string => '  - '.$this->indented($entry, 4, startsALine: true))
            ->all();

        if ($entries === []) {
            return '';
        }

        return '- **'.$this->line($field->label)."**\n".implode("\n", $entries);
    }

    /** @param  Collection<int, Field>  $children */
    private function rowLine(FieldValueRow $row, Collection $children): string
    {
        $answers = $children
            ->map(function (Field $child) use ($row): ?array {
                $value = $row->values->firstWhere('field_id', $child->id);
                $written = $value === null ? null : $this->formatter()->format($value);

                return $written === null ? null : ['label' => $child->label, 'value' => $written];
            })
            ->filter()
            ->values();

        if ($answers->count() === 1 && $children->count() === 1) {
            return $answers->first()['value'];
        }

        return $answers
            ->map(fn (array $answer): string => __(':label: :value', ['label' => $answer['label'], 'value' => $answer['value']]))
            ->implode('; ');
    }

    private function relationships(): string
    {
        $parts = [];

        foreach (new RelationshipsBlock(vault: $this->vault, contact: $this->contact)->groups() as $group) {
            $items = array_map(function (array $row): string {
                $facts = array_filter([
                    $row['name'],
                    $row['whenLine'],
                    $row['hasEnded'] ? __('Ended') : null,
                ]);

                return $this->item($row['role'], implode(__(', '), $facts));
            }, $group['rows']);

            $parts[] = '### '.$this->line($group['label'])."\n\n".implode("\n", $items);
        }

        if ($parts === []) {
            return '';
        }

        return '## '.__('Relationships')."\n\n".implode("\n\n", $parts);
    }

    private function lifeEvents(): string
    {
        $period = new GroupPeriod($this->user);
        $items = [];

        foreach ($this->lifeEventRecords() as $lifeEvent) {
            $title = $lifeEvent->name === $lifeEvent->lifeEventType->name
                ? $lifeEvent->name
                : "{$lifeEvent->name} ({$lifeEvent->lifeEventType->name})";

            $details = [];

            $others = $lifeEvent->participants
                ->map(fn (LifeEventParticipant $participant): mixed => $participant->participant)
                ->filter(fn (mixed $participant): bool => $participant instanceof Contact && $participant->id !== $this->contact->id)
                ->map(fn (Contact $participant): string => $participant->name);

            if ($others->isNotEmpty()) {
                $details[] = __('With :names', ['names' => $others->implode(__(', '))]);
            }

            if ($lifeEvent->duration_in_minutes !== null) {
                $details[] = trans_choice(':count minute|:count minutes', $lifeEvent->duration_in_minutes);
            }

            if (filled($lifeEvent->location)) {
                $details[] = $lifeEvent->location;
            }

            foreach ($lifeEvent->fieldValues->sortBy(fn (FieldValue $value): int => $value->field->position) as $value) {
                $written = $this->reaches($value->field) ? $this->formatter()->format($value) : null;

                if ($written !== null) {
                    $details[] = __(':label: :value', ['label' => $value->field->label, 'value' => $written]);
                }
            }

            if (filled($lifeEvent->narrative)) {
                $details[] = $lifeEvent->narrative;
            }

            $items[] = $this->item((string) $period->date($lifeEvent->happened_on, $lifeEvent->date_precision), $title)
                .implode('', array_map(fn (string $detail): string => "\n  - ".$this->indented($detail, 4, startsALine: true), $details));
        }

        return $this->part(__('Life events'), $items);
    }

    /** @return EloquentCollection<int, LifeEvent> */
    private function lifeEventRecords(): EloquentCollection
    {
        return LifeEvent::query()
            ->where('account_id', $this->contact->account_id)
            ->where('vault_id', $this->vault->id)
            ->whereIn('id', LifeEventParticipant::query()
                ->select('life_event_id')
                ->where('participant_type', $this->contact->getMorphClass())
                ->where('participant_id', $this->contact->id))
            ->with([
                'lifeEventType',
                'participants.participant',
                'fieldValues' => fn ($query) => $query
                    ->attached()
                    ->whereNull('field_value_row_id')
                    ->with(['field', 'valueListItem', 'file', 'relatedContact', 'relatedEntity']),
            ])
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->get();
    }

    private function groups(): string
    {
        if (! FlagState::isOn('groups', $this->user->account)) {
            return '';
        }

        $items = array_map(
            fn (array $membership): string => $this->item($membership['name'], implode(__(', '), array_filter([$membership['role'], $membership['period']]))),
            new ContactGroupsBlock(user: $this->user, vault: $this->vault, contact: $this->contact)->memberships(),
        );

        return $this->part(__('Groups'), $items);
    }

    private function reminders(): string
    {
        $today = CarbonImmutable::now($this->user->timezone)->toDateString();

        $items = $this->contact->reminders()
            ->active()
            ->with(['field'])
            ->orderBy('id')
            ->get()
            ->filter(fn (Reminder $reminder): bool => $reminder->field === null || $this->reaches($reminder->field))
            ->map(function (Reminder $reminder) use ($today): string {
                $next = ReminderOccurrence::query()
                    ->where('reminder_id', $reminder->id)
                    ->active()
                    ->where('local_date', '>=', $today)
                    ->orderBy('local_date')
                    ->first();

                $facts = array_filter([
                    ReminderWords::cadence($reminder->cadence, $reminder->frequency_interval),
                    $next === null ? null : __('Next on :date', ['date' => DateFormatEnum::write($next->local_date, $this->dateFormat())]),
                ]);

                return $this->item($reminder->name, implode(__(', '), $facts));
            })
            ->all();

        return $this->part(__('Reminders'), $items);
    }

    private function loans(): string
    {
        $items = array_map(
            fn (array $loan): string => $this->item($loan['possession'], implode(__(', '), [$loan['verb'], $loan['status'], $loan['period']])),
            new ContactLoansBlock(user: $this->user, contact: $this->contact)->loans(),
        );

        return $this->part(__('Loans'), $items);
    }

    /** @param  list<string>  $items */
    private function part(string $heading, array $items): string
    {
        if ($items === []) {
            return '';
        }

        return "## {$heading}\n\n".implode("\n", $items);
    }

    private function item(string $label, ?string $value): string
    {
        if (blank($value)) {
            return '';
        }

        return '- **'.$this->line($label).'**: '.$this->indented($value, 2, startsALine: false);
    }

    private function valueOf(Field $field): ?string
    {
        $value = $this->values()->get($field->id);

        return $value === null ? null : $this->formatter()->format($value);
    }

    private function reaches(Field $field): bool
    {
        return $this->reachesSensitiveFields || ! $field->is_sensitive;
    }

    /**
     * Text the reader wrote, kept as written except where it could leave the list item it belongs
     * to: every further line is indented under the item, and a line that would open a heading, a
     * quotation, a list, a table or a code fence starts with a backslash instead. The first line is
     * escaped only when it starts a line of its own, which it does under a bare list marker.
     */
    private function indented(string $text, int $spaces, bool $startsALine): string
    {
        $lines = preg_split('/\R/u', mb_trim($text)) ?: [];
        $first = (string) array_shift($lines);
        $first = $startsALine ? $this->line($first) : $first;

        $rest = array_map(
            fn (string $line): string => mb_trim($line) === '' ? '' : str_repeat(' ', $spaces).$this->line(mb_ltrim($line)),
            $lines,
        );

        return implode("\n", [$first, ...$rest]);
    }

    private function line(string $text): string
    {
        if (preg_match('/^\d+[.)]/u', $text) === 1) {
            return (string) preg_replace('/^(\d+)([.)])/u', '$1\\\\$2', $text);
        }

        if (preg_match('/^[#>|*+\-=_~`]/u', $text) === 1) {
            return "\\{$text}";
        }

        return $text;
    }

    /** @return Collection<int, FieldValue> */
    private function values(): Collection
    {
        return once(fn (): Collection => $this->contact
            ->fieldValues()
            ->attached()
            ->whereNull('field_value_row_id')
            ->with(['field', 'valueListItem', 'file.vault', 'relatedContact', 'relatedEntity'])
            ->get()
            ->keyBy('field_id'));
    }

    /** @return Collection<int|string, EloquentCollection<int, FieldValueRow>> */
    private function rows(): Collection
    {
        return once(fn (): Collection => $this->contact
            ->fieldValueRows()
            ->with(['values' => fn ($query) => $query->attached()->with(['field', 'valueListItem', 'file.vault', 'relatedContact', 'relatedEntity'])])
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->groupBy('field_id')
            ->toBase());
    }

    private function formatter(): FieldValueFormatter
    {
        return once(fn (): FieldValueFormatter => new FieldValueFormatter($this->user));
    }

    private function dateFormat(): string
    {
        return $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];
    }
}
