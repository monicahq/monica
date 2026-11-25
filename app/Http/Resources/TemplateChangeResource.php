<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\TemplateChangeOutcomeEnum;
use App\Models\TemplateChange;
use App\Models\TemplateChangeItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin TemplateChange */
class TemplateChangeResource extends JsonResource
{
    public const int NOT_MOVED_SHOWN = 100;

    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $singular = $this->entityType->name_singular ?? __('contact');

        return [
            'type' => 'template_change',
            'id' => $this->uuid,
            'attributes' => [
                'status' => $this->isRunning() ? 'running' : 'finished',
                'record_kind' => $this->entityType->uuid ?? 'contacts',
                'from' => $this->fromTemplate->uuid,
                'to' => $this->structureVersion->structure->owner->uuid,
                'to_version' => $this->structureVersion->version_number,
                'counts' => [
                    'total' => $this->items_total,
                    'done' => $this->items()->whereNotNull('outcome')->count(),
                    'moved' => $this->moved_count,
                    'skipped' => $this->skipped_count,
                    'failed' => $this->failed_count,
                ],
                'not_moved' => $this->items()
                    ->whereIn('outcome', [TemplateChangeOutcomeEnum::Skipped, TemplateChangeOutcomeEnum::Failed])
                    ->with('record')
                    ->orderBy('id')
                    ->limit(self::NOT_MOVED_SHOWN)
                    ->get()
                    ->map(fn (TemplateChangeItem $item): array => [
                        'id' => $item->record?->uuid,
                        'name' => $item->record?->name,
                        'outcome' => $item->outcome->value,
                        'reason' => $item->reason?->sentence($singular),
                    ])
                    ->all(),
                'created_at' => $this->created_at->timestamp,
                'ended_at' => $this->ended_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.templateChanges.show', ['vault' => $this->vault->uuid, 'templateChange' => $this->uuid]),
            ],
        ];
    }
}
