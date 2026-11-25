<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property array{
 *     source: Template,
 *     target: Template,
 *     blockedIds: list<string>,
 *     preview: array{
 *         counts: array{ready: int, unchanged: int, setAside: int, restored: int, remindersPaused: int, remindersResumed: int},
 *         setAside: list<array{label: string, section: string, records: int}>,
 *         restored: list<array{label: string, section: string, records: int}>,
 *         remindersPaused: list<array{name: string, records: int}>,
 *         remindersResumed: list<array{name: string, records: int}>,
 *         blocked: list<int>,
 *         hiddenCount: int
 *     }
 * } $resource
 */
class BulkTemplateChangePreviewResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $preview = $this->resource['preview'];
        $counts = $preview['counts'];

        return [
            'type' => 'template_change_preview',
            'attributes' => [
                'from' => $this->resource['source']->uuid,
                'to' => $this->resource['target']->uuid,
                'to_version' => $this->resource['target']->structure->publishedVersion?->version_number,
                'counts' => [
                    'ready' => $counts['ready'],
                    'unchanged' => $counts['unchanged'],
                    'set_aside' => $counts['setAside'],
                    'restored' => $counts['restored'],
                    'reminders_paused' => $counts['remindersPaused'],
                    'reminders_resumed' => $counts['remindersResumed'],
                ],
                'set_aside' => $preview['setAside'],
                'restored' => $preview['restored'],
                'reminders_paused' => $preview['remindersPaused'],
                'reminders_resumed' => $preview['remindersResumed'],
                'blocked' => $this->resource['blockedIds'],
                'hidden_count' => $preview['hiddenCount'],
            ],
        ];
    }
}
