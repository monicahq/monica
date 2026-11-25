<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\ReminderInactiveReasonEnum;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\StructureVersion;
use Illuminate\Support\Collection;

class TemplateChangePreview
{
    private int $hiddenCount = 0;

    public function __construct(
        private readonly Contact $contact,
        private readonly StructureVersion $target,
        private readonly bool $namesSensitiveFields,
    ) {}

    /**
     * @return array{
     *     isChange: bool,
     *     setAside: list<array{field_id: int, label: string, section: string, count: int}>,
     *     broughtBack: list<array{field_id: int, label: string, section: string, count: int}>,
     *     remindersStopped: list<array{id: string, label: string}>,
     *     remindersRestarted: list<array{id: string, label: string}>,
     *     hiddenCount: int
     * }
     */
    public function toArray(): array
    {
        if ($this->contact->structure_version_id === $this->target->id) {
            return [
                'isChange' => false,
                'setAside' => [],
                'broughtBack' => [],
                'remindersStopped' => [],
                'remindersRestarted' => [],
                'hiddenCount' => 0,
            ];
        }

        $correspondence = new FieldCorrespondence($this->target);
        $landing = array_fill_keys($correspondence->carriedFieldIds(), true) + $correspondence->counterpartsFor($this->contact);

        $values = FieldValue::query()
            ->where('valuable_type', $this->contact->getMorphClass())
            ->where('valuable_id', $this->contact->id)
            ->with('field.sectionVersion.section')
            ->get();

        $leaving = $values->filter(fn (FieldValue $value): bool => ! $value->is_detached && ! isset($landing[$value->field_id]));
        $returning = $values->filter(fn (FieldValue $value): bool => $value->is_detached && isset($landing[$value->field_id]));

        $stopped = Reminder::query()
            ->whereIn('field_value_id', $leaving->modelKeys())
            ->active()
            ->with('fieldValue.field')
            ->get();

        $restarted = Reminder::query()
            ->whereIn('field_value_id', $returning->modelKeys())
            ->where('inactive_reason', ReminderInactiveReasonEnum::FieldRemoved)
            ->with('fieldValue.field')
            ->get();

        return [
            'isChange' => true,
            'setAside' => $this->fields($leaving),
            'broughtBack' => $this->fields($returning),
            'remindersStopped' => $this->reminders($stopped),
            'remindersRestarted' => $this->reminders($restarted),
            'hiddenCount' => $this->hiddenCount,
        ];
    }

    /**
     * @param  Collection<int, FieldValue>  $values
     * @return list<array{field_id: int, label: string, section: string, count: int}>
     */
    private function fields(Collection $values): array
    {
        $named = $values->reject(fn (FieldValue $value): bool => $this->hides($value->field->is_sensitive));
        $this->hiddenCount += $values->count() - $named->count();

        return $named
            ->groupBy('field_id')
            ->map(fn (Collection $answers): array => [
                'field_id' => $answers->first()->field_id,
                'label' => $answers->first()->field->label,
                'section' => $answers->first()->field->sectionVersion->section->name,
                'count' => $answers->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Reminder>  $reminders
     * @return list<array{id: string, label: string}>
     */
    private function reminders(Collection $reminders): array
    {
        $named = $reminders->reject(fn (Reminder $reminder): bool => $this->hides((bool) $reminder->fieldValue?->field?->is_sensitive));
        $this->hiddenCount += $reminders->count() - $named->count();

        return $named
            ->map(fn (Reminder $reminder): array => ['id' => $reminder->uuid, 'label' => $reminder->name])
            ->values()
            ->all();
    }

    private function hides(bool $isSensitive): bool
    {
        return $isSensitive && ! $this->namesSensitiveFields;
    }
}
