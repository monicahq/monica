<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\RecordKind;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property array{
 *     kind: RecordKind,
 *     statistics: array{
 *         overview: array{records: int, templatesInUse: int, onOlderVersions: int, archived: int, partial: int|null},
 *         templates: list<array{template: Template, isDefault: bool, isActive: bool, records: int, share: int, publishedVersion: int|null, versions: list<array{id: int, number: int, records: int, isPublished: bool}>}>
 *     }
 * } $resource
 */
class RecordStatisticsResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $kind = $this->resource['kind'];
        $statistics = $this->resource['statistics'];

        return [
            'type' => 'record_statistics',
            'attributes' => [
                'kind' => [
                    'id' => $kind->apiSegment(),
                    'singular' => $kind->singular(),
                    'plural' => $kind->plural(),
                ],
                'overview' => [
                    'records' => $statistics['overview']['records'],
                    'templates_in_use' => $statistics['overview']['templatesInUse'],
                    'on_older_versions' => $statistics['overview']['onOlderVersions'],
                    'archived' => $statistics['overview']['archived'],
                    'partial' => $statistics['overview']['partial'],
                ],
                'templates' => array_map(fn (array $row): array => [
                    'id' => $row['template']->uuid,
                    'name' => $row['template']->name,
                    'is_default' => $row['isDefault'],
                    'is_active' => $row['isActive'],
                    'records' => $row['records'],
                    'share' => $row['share'],
                    'published_version' => $row['publishedVersion'],
                    'versions' => array_map(fn (array $version): array => [
                        'number' => $version['number'],
                        'records' => $version['records'],
                        'is_published' => $version['isPublished'],
                    ], $row['versions']),
                ], $statistics['templates']),
            ],
            'links' => [
                'self' => route('api.v1.recordKinds.statistics.show', ['vault' => $kind->vault->uuid, 'recordKind' => $kind->apiSegment()]),
            ],
        ];
    }
}
