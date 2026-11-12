<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PossessionLocationStay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin PossessionLocationStay */
class PossessionLocationStayResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['possession.vault', 'location']);

        return [
            'type' => 'possession_location_stay',
            'id' => $this->uuid,
            'attributes' => [
                'location_id' => $this->location->uuid,
                'location_name' => $this->location->name,
                'arrived_on' => $this->arrived_on?->toDateString(),
                'left_on' => $this->left_on?->toDateString(),
                'is_current' => $this->isOpen(),
                'reason' => $this->reason,
                'note' => $this->note,
                'created_at' => $this->created_at->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.possessions.locationStays.show', [$this->possession->vault->slug, $this->possession->uuid, $this->uuid]),
                'web' => route('possessions.locationStays.index', [$this->possession->vault, $this->possession]),
            ],
        ];
    }
}
