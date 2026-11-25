<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\FieldTypeEnum;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FieldCompletion
{
    public const string LOWEST_FIRST = 'lowest';

    public const string FORM_ORDER = 'form';

    /** @var array<string, true> */
    private array $hidden = [];

    public function __construct(
        private readonly RecordKind $kind,
        private readonly Template $template,
        private readonly ?int $versionNumber,
        private readonly string $order,
        private readonly bool $namesSensitiveFields,
    ) {}

    /**
     * @return array{
     *     records: int,
     *     sections: list<array{name: string, fields: list<array{key: string, label: string, filled: int, offeredTo: int, percentage: int|null, availableFrom: int|null}>}>,
     *     hiddenCount: int
     * }
     */
    public function toArray(): array
    {
        $recordsPerVersion = $this->recordsPerVersion();

        if ($recordsPerVersion === []) {
            return ['records' => 0, 'sections' => [], 'hiddenCount' => 0];
        }

        $versions = StructureVersion::query()
            ->whereIn('id', array_keys($recordsPerVersion))
            ->with('assembly')
            ->orderBy('version_number')
            ->get();

        $offered = $this->offeredFields($versions, $recordsPerVersion);
        $filled = $this->filledRecordsPerField(
            fieldIds: array_merge(...array_column($offered, 'fieldIds')),
            versionIds: array_keys($recordsPerVersion),
        );

        $sections = [];

        foreach ($offered as $identity) {
            $row = [
                'key' => $identity['field']->key,
                'label' => $identity['field']->label,
                'filled' => array_sum(array_map(fn (int $id): int => $filled[$id] ?? 0, $identity['fieldIds'])),
                'offeredTo' => $identity['offeredTo'],
                'percentage' => null,
                'availableFrom' => count($identity['versionNumbers']) === $versions->count() ? null : min($identity['versionNumbers']),
            ];
            $row['percentage'] = $row['offeredTo'] === 0 ? null : (int) round($row['filled'] * 100 / $row['offeredTo']);

            $sections[$identity['section']]['name'] = $identity['section'];
            $sections[$identity['section']]['fields'][] = $row;
        }

        if ($this->order === self::LOWEST_FIRST) {
            foreach ($sections as $name => $section) {
                usort($section['fields'], fn (array $a, array $b): int => ($a['percentage'] ?? 101) <=> ($b['percentage'] ?? 101));
                $sections[$name] = $section;
            }
        }

        return [
            'records' => array_sum($recordsPerVersion),
            'sections' => array_values($sections),
            'hiddenCount' => count($this->hidden),
        ];
    }

    /** @return array<int, int> */
    private function recordsPerVersion(): array
    {
        $versionIds = $this->template->structure->versions()
            ->when($this->versionNumber !== null, fn (Builder $query) => $query->where('version_number', $this->versionNumber))
            ->pluck('id');

        $query = $this->kind->recordQuery()
            ->whereIn('structure_version_id', $versionIds)
            ->where('is_archived', false);

        if ($this->kind->isPeople()) {
            $query->where('is_partial', false);
        }

        return $query->toBase()
            ->select('structure_version_id')
            ->addSelect(DB::raw('count(*) as aggregate'))
            ->groupBy('structure_version_id')
            ->pluck('aggregate', 'structure_version_id')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
    }

    /**
     * @param  Collection<int, StructureVersion>  $versions
     * @param  array<int, int>  $recordsPerVersion
     * @return array<string, array{field: Field, section: string, fieldIds: list<int>, offeredTo: int, versionNumbers: list<int>}>
     */
    private function offeredFields(Collection $versions, array $recordsPerVersion): array
    {
        $offered = [];

        foreach ($versions->sortByDesc('version_number') as $version) {
            foreach ($version->assembly as $placement) {
                /** @var StructureVersionSection $placement */
                $section = $placement->sectionVersion->section;

                foreach ($placement->sectionVersion->fields->whereNull('parent_field_id')->sortBy('position') as $field) {
                    if (! $field->type->hasAnInput()) {
                        continue;
                    }

                    $identity = "{$section->id}|{$field->key}|{$field->type->value}";

                    if ($field->is_sensitive && ! $this->namesSensitiveFields) {
                        $this->hidden[$identity] = true;

                        continue;
                    }

                    $offered[$identity] ??= ['field' => $field, 'section' => $section->name, 'fieldIds' => [], 'offeredTo' => 0, 'versionNumbers' => []];

                    if (in_array($version->version_number, $offered[$identity]['versionNumbers'], true)) {
                        continue;
                    }

                    $offered[$identity]['fieldIds'][] = $field->id;
                    $offered[$identity]['offeredTo'] += $recordsPerVersion[$version->id];
                    $offered[$identity]['versionNumbers'][] = $version->version_number;
                }
            }
        }

        foreach ($offered as $identity => $entry) {
            $offered[$identity]['fieldIds'] = array_values(array_unique($entry['fieldIds']));
        }

        return $offered;
    }

    /**
     * @param  list<int>  $fieldIds
     * @param  list<int>  $versionIds
     * @return array<int, int>
     */
    private function filledRecordsPerField(array $fieldIds, array $versionIds): array
    {
        $records = $this->kind->recordQuery()
            ->whereIn('structure_version_id', $versionIds)
            ->where('is_archived', false)
            ->when($this->kind->isPeople(), fn (Builder $query) => $query->where('is_partial', false))
            ->select('id');

        $values = FieldValue::query()
            ->where('vault_id', $this->kind->vault->id)
            ->where('valuable_type', $this->kind->morphClass())
            ->whereIn('field_id', $fieldIds)
            ->where('is_detached', false)
            ->whereIn('valuable_id', $records)
            ->toBase()
            ->select('field_id')
            ->addSelect(DB::raw('count(distinct valuable_id) as aggregate'))
            ->groupBy('field_id')
            ->pluck('aggregate', 'field_id');

        $repeaters = Field::query()->whereIn('id', $fieldIds)->where('type', FieldTypeEnum::Repeater)->pluck('id');

        $rows = $repeaters->isEmpty() ? collect() : FieldValueRow::query()
            ->where('valuable_type', $this->kind->morphClass())
            ->whereIn('field_id', $repeaters)
            ->whereIn('valuable_id', $records)
            ->toBase()
            ->select('field_id')
            ->addSelect(DB::raw('count(distinct valuable_id) as aggregate'))
            ->groupBy('field_id')
            ->pluck('aggregate', 'field_id');

        return $values->union($rows)->map(fn (mixed $count): int => (int) $count)->all();
    }
}
