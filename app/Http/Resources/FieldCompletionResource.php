<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property array{
 *     template: Template,
 *     version: int|null,
 *     completion: array{
 *         records: int,
 *         sections: list<array{name: string, fields: list<array{key: string, label: string, filled: int, offeredTo: int, percentage: int|null, availableFrom: int|null}>}>,
 *         hiddenCount: int
 *     }
 * } $resource
 */
class FieldCompletionResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $completion = $this->resource['completion'];

        return [
            'type' => 'field_completion',
            'attributes' => [
                'template' => $this->resource['template']->uuid,
                'version' => $this->resource['version'],
                'records' => $completion['records'],
                'sections' => array_map(fn (array $section): array => [
                    'name' => $section['name'],
                    'fields' => array_map(fn (array $field): array => [
                        'key' => $field['key'],
                        'label' => $field['label'],
                        'filled' => $field['filled'],
                        'offered_to' => $field['offeredTo'],
                        'percentage' => $field['percentage'],
                        'available_from' => $field['availableFrom'],
                    ], $section['fields']),
                ], $completion['sections']),
                'hidden_count' => $completion['hiddenCount'],
            ],
        ];
    }
}
