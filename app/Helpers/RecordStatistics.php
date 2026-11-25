<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\StructureVersion;
use App\Models\Template;
use Illuminate\Support\Facades\DB;

class RecordStatistics
{
    /** @var array<int, int> */
    private array $recordsPerVersion = [];

    private int $archived = 0;

    private int $partial = 0;

    public function __construct(
        private readonly RecordKind $kind,
    ) {}

    /**
     * @return array{
     *     overview: array{records: int, templatesInUse: int, onOlderVersions: int, archived: int, partial: int|null},
     *     templates: list<array{template: Template, isDefault: bool, isActive: bool, records: int, share: int, publishedVersion: int|null, versions: list<array{id: int, number: int, records: int, isPublished: bool}>}>
     * }
     */
    public function toArray(): array
    {
        $this->count();

        $total = array_sum($this->recordsPerVersion);
        $versions = StructureVersion::query()
            ->whereIn('id', array_keys($this->recordsPerVersion))
            ->get(['id', 'structure_id', 'version_number'])
            ->groupBy('structure_id');
        $defaultTemplateId = $this->kind->defaultTemplateId();

        $templates = [];
        $onOlderVersions = 0;

        foreach ($this->kind->templates() as $template) {
            $structure = $template->structure;
            $used = $versions->get($structure->id, collect())
                ->sortByDesc('version_number')
                ->map(fn (StructureVersion $version): array => [
                    'id' => $version->id,
                    'number' => $version->version_number,
                    'records' => $this->recordsPerVersion[$version->id],
                    'isPublished' => $version->id === $structure->published_version_id,
                ])
                ->values()
                ->all();
            $records = array_sum(array_column($used, 'records'));
            $isOffered = $template->is_active && $structure->published_version_id !== null;

            if ($records === 0 && ! $isOffered) {
                continue;
            }

            foreach ($used as $version) {
                $onOlderVersions += $version['isPublished'] ? 0 : $version['records'];
            }

            $templates[] = [
                'template' => $template,
                'isDefault' => $template->id === $defaultTemplateId,
                'isActive' => $template->is_active,
                'records' => $records,
                'share' => $total === 0 ? 0 : (int) round($records * 100 / $total),
                'publishedVersion' => $structure->publishedVersion?->version_number,
                'versions' => $used,
            ];
        }

        return [
            'overview' => [
                'records' => $total,
                'templatesInUse' => count(array_filter($templates, fn (array $row): bool => $row['records'] > 0)),
                'onOlderVersions' => $onOlderVersions,
                'archived' => $this->archived,
                'partial' => $this->kind->isPeople() ? $this->partial : null,
            ],
            'templates' => $templates,
        ];
    }

    private function count(): void
    {
        $columns = $this->kind->isPeople()
            ? ['structure_version_id', 'is_archived', 'is_partial']
            : ['structure_version_id', 'is_archived'];

        $rows = $this->kind->recordQuery()
            ->toBase()
            ->select($columns)
            ->addSelect(DB::raw('count(*) as aggregate'))
            ->groupBy($columns)
            ->get();

        foreach ($rows as $row) {
            $count = (int) $row->aggregate;

            if ((bool) $row->is_archived) {
                $this->archived += $count;

                continue;
            }

            $versionId = (int) $row->structure_version_id;
            $this->recordsPerVersion[$versionId] = ($this->recordsPerVersion[$versionId] ?? 0) + $count;
            $this->partial += (bool) ($row->is_partial ?? false) ? $count : 0;
        }
    }
}
