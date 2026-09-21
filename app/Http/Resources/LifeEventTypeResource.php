<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\LifeEventType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin LifeEventType */
class LifeEventTypeResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'life_event_type',
            'id' => $this->uuid,
            'attributes' => [
                'life_event_category_id' => $this->lifeEventCategory->uuid,
                'name' => $this->name,
                'description' => $this->description,
                'icon' => $this->icon,
                'colour' => $this->colour,
                'position' => $this->position,
                'is_system' => $this->is_system,
                'is_active' => $this->is_active,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.lifeEventTypes.show', $this->uuid),
            ],
        ];
    }
}
