<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PossessionMaintenanceRecord;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin PossessionMaintenanceRecord */
class PossessionMaintenanceRecordResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['possession.vault', 'possession.maintenanceRecords', 'conditionBefore', 'conditionAfter']);

        $today = CarbonImmutable::now($request->user()->timezone ?? config('app.timezone'));

        return [
            'type' => 'possession_maintenance_record',
            'id' => $this->uuid,
            'attributes' => [
                'maintenance_type' => $this->type->value,
                'title' => $this->title,
                'performed_by' => $this->performed_by,
                'performed_on' => $this->performed_on->format('Y-m-d'),
                'cost' => $this->cost,
                'currency' => $this->currency?->value,
                'condition_before' => $this->conditionBefore === null ? null : ['id' => $this->conditionBefore->uuid, 'name' => $this->conditionBefore->name],
                'condition_after' => $this->conditionAfter === null ? null : ['id' => $this->conditionAfter->uuid, 'name' => $this->conditionAfter->name],
                'next_due_on' => $this->next_due_on?->format('Y-m-d'),
                'is_overdue' => $this->possession->overdueMaintenanceRecord($today)?->is($this->resource) ?? false,
                'description' => $this->description,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.possessions.maintenanceRecords.show', [$this->possession->vault->slug, $this->possession->uuid, $this->uuid]),
                'web' => route('possessions.maintenanceRecords.index', [$this->possession->vault, $this->possession]),
            ],
        ];
    }
}
