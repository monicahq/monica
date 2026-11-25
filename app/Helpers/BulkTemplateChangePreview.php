<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\ReminderInactiveReasonEnum;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\Reminder;
use Illuminate\Support\Collection;

class BulkTemplateChangePreview
{
    /** @var array<string, true> */
    private array $hidden = [];

    /** @var Collection<int, Field> */
    private Collection $fields;

    public function __construct(
        private readonly TemplateChangeBatch $batch,
        private readonly bool $namesSensitiveFields,
    ) {}

    /**
     * @return array{
     *     counts: array{ready: int, unchanged: int, setAside: int, restored: int, remindersPaused: int, remindersResumed: int},
     *     setAside: list<array{label: string, section: string, records: int}>,
     *     restored: list<array{label: string, section: string, records: int}>,
     *     remindersPaused: list<array{name: string, records: int}>,
     *     remindersResumed: list<array{name: string, records: int}>,
     *     blocked: list<int>,
     *     hiddenCount: int
     * }
     */
    public function toArray(): array
    {
        $movable = $this->batch->movable();
        $held = $this->heldFieldsPerRecord($movable->keys()->all());
        $correspondence = new FieldCorrespondence($this->batch->target);
        $carried = array_fill_keys($correspondence->carriedFieldIds(), true);

        $this->fields = Field::query()
            ->whereIn('id', collect($held)->flatMap(fn (array $record): array => [...array_keys($record['values']), ...$record['rows']])->unique()->values())
            ->with('sectionVersion.section')
            ->get()
            ->keyBy('id');

        $landingPerSignature = [];
        $leavingPerRecord = [];
        $returningPerRecord = [];
        $setAside = [];
        $restored = [];

        foreach ($movable->keys() as $recordId) {
            $record = $held[$recordId] ?? ['values' => [], 'rows' => []];
            $signature = $this->signature($record);
            $landing = $landingPerSignature[$signature] ??= $this->landing($record, $correspondence, $carried);

            foreach ($record['values'] as $fieldId => $isDetached) {
                if (! $isDetached && ! isset($landing[$fieldId])) {
                    $leavingPerRecord[$recordId][$fieldId] = true;
                    $setAside[$fieldId] = ($setAside[$fieldId] ?? 0) + 1;
                }

                if ($isDetached && isset($landing[$fieldId])) {
                    $returningPerRecord[$recordId][$fieldId] = true;
                    $restored[$fieldId] = ($restored[$fieldId] ?? 0) + 1;
                }
            }
        }

        [$paused, $resumed] = $this->reminders($leavingPerRecord, $returningPerRecord);
        $changed = array_keys($leavingPerRecord + $returningPerRecord);

        return [
            'counts' => [
                'ready' => $movable->count(),
                'unchanged' => $movable->count() - count($changed),
                'setAside' => count($leavingPerRecord),
                'restored' => count($returningPerRecord),
                'remindersPaused' => array_sum($paused),
                'remindersResumed' => array_sum($resumed),
            ],
            'setAside' => $this->namedFields($setAside),
            'restored' => $this->namedFields($restored),
            'remindersPaused' => $this->namedReminders($paused),
            'remindersResumed' => $this->namedReminders($resumed),
            'blocked' => array_keys($this->batch->blocked()),
            'hiddenCount' => count($this->hidden),
        ];
    }

    /**
     * @param  list<int>  $recordIds
     * @return array<int, array{values: array<int, bool>, rows: list<int>}>
     */
    private function heldFieldsPerRecord(array $recordIds): array
    {
        $held = [];
        $morph = $this->batch->kind->morphClass();

        foreach (array_chunk($recordIds, 1000) as $chunk) {
            FieldValue::query()
                ->where('valuable_type', $morph)
                ->whereIn('valuable_id', $chunk)
                ->toBase()
                ->select(['valuable_id', 'field_id', 'is_detached'])
                ->distinct()
                ->get()
                ->each(function (object $row) use (&$held): void {
                    $isDetached = (bool) $row->is_detached;
                    $current = $held[$row->valuable_id]['values'][$row->field_id] ?? $isDetached;
                    $held[$row->valuable_id]['values'][$row->field_id] = $current && $isDetached;
                    $held[$row->valuable_id]['rows'] ??= [];
                });

            FieldValueRow::query()
                ->where('valuable_type', $morph)
                ->whereIn('valuable_id', $chunk)
                ->toBase()
                ->select(['valuable_id', 'field_id'])
                ->distinct()
                ->get()
                ->each(function (object $row) use (&$held): void {
                    $held[$row->valuable_id]['values'] ??= [];
                    $held[$row->valuable_id]['rows'][] = (int) $row->field_id;
                });
        }

        return $held;
    }

    /** @param array{values: array<int, bool>, rows: list<int>} $record */
    private function signature(array $record): string
    {
        $values = $record['values'];
        ksort($values);
        $rows = $record['rows'];
        sort($rows);

        return json_encode([$values, $rows], JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array{values: array<int, bool>, rows: list<int>}  $record
     * @param  array<int, true>  $carried
     * @return array<int, true>
     */
    private function landing(array $record, FieldCorrespondence $correspondence, array $carried): array
    {
        $fieldIds = array_unique([...array_keys($record['values']), ...$record['rows']]);
        $heldIds = array_filter($fieldIds, fn (int $id): bool => ! isset($carried[$id]));
        $filled = array_values(array_filter($fieldIds, fn (int $id): bool => isset($carried[$id])));

        if ($heldIds === []) {
            return $carried;
        }

        $heldFields = $this->fields->only($heldIds)->values();

        return $carried + array_fill_keys(array_keys($correspondence->counterparts($heldFields, $filled)), true);
    }

    /**
     * @param  array<int, array<int, true>>  $leaving
     * @param  array<int, array<int, true>>  $returning
     * @return array{0: array<string, int>, 1: array<string, int>}
     */
    private function reminders(array $leaving, array $returning): array
    {
        $paused = [];
        $resumed = [];
        $recordIds = array_keys($leaving + $returning);

        foreach (array_chunk($recordIds, 1000) as $chunk) {
            Reminder::query()
                ->join('field_values', 'field_values.id', '=', 'reminders.field_value_id')
                ->where('field_values.valuable_type', $this->batch->kind->morphClass())
                ->whereIn('field_values.valuable_id', $chunk)
                ->toBase()
                ->select(['reminders.name', 'reminders.is_active', 'reminders.inactive_reason', 'field_values.valuable_id', 'field_values.field_id'])
                ->get()
                ->each(function (object $row) use ($leaving, $returning, &$paused, &$resumed): void {
                    $field = $this->fields->get($row->field_id);
                    $name = $field instanceof Field && $this->hides($field) ? null : $row->name;

                    if ((bool) $row->is_active && isset($leaving[$row->valuable_id][$row->field_id])) {
                        $paused[$name ?? ''] = ($paused[$name ?? ''] ?? 0) + 1;
                    }

                    if (! (bool) $row->is_active && $row->inactive_reason === ReminderInactiveReasonEnum::FieldRemoved->value && isset($returning[$row->valuable_id][$row->field_id])) {
                        $resumed[$name ?? ''] = ($resumed[$name ?? ''] ?? 0) + 1;
                    }
                });
        }

        return [$paused, $resumed];
    }

    /**
     * @param  array<int, int>  $recordsPerField
     * @return list<array{label: string, section: string, records: int}>
     */
    private function namedFields(array $recordsPerField): array
    {
        $named = [];

        foreach ($recordsPerField as $fieldId => $records) {
            $field = $this->fields->get($fieldId);

            if (! $field instanceof Field || $this->hides($field)) {
                continue;
            }

            $section = $field->sectionVersion->section->name;
            $key = "{$section}|{$field->label}";
            $named[$key] = [
                'label' => $field->label,
                'section' => $section,
                'records' => ($named[$key]['records'] ?? 0) + $records,
            ];
        }

        usort($named, fn (array $a, array $b): int => [$b['records'], $a['label']] <=> [$a['records'], $b['label']]);

        return $named;
    }

    /**
     * @param  array<string, int>  $recordsPerName
     * @return list<array{name: string, records: int}>
     */
    private function namedReminders(array $recordsPerName): array
    {
        unset($recordsPerName['']);

        return collect($recordsPerName)
            ->map(fn (int $records, string $name): array => ['name' => $name, 'records' => $records])
            ->sortByDesc('records')
            ->values()
            ->all();
    }

    private function hides(Field $field): bool
    {
        if (! $field->is_sensitive || $this->namesSensitiveFields) {
            return false;
        }

        $this->hidden["{$field->sectionVersion->section_id}|{$field->key}"] = true;

        return true;
    }
}
