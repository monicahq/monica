<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\LifeEventCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin LifeEventCategory */
class LifeEventCategoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'life_event_category',
            'id' => $this->uuid,
            'attributes' => [
                'name' => $this->name,
                'position' => $this->position,
                'is_system' => $this->is_system,
                'is_active' => $this->is_active,
                'life_event_type_count' => $this->lifeEventTypeCount(),
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.lifeEventCategories.show', $this->uuid),
            ],
        ];
    }
}
