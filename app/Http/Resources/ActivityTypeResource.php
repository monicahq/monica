<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ActivityType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin ActivityType */
class ActivityTypeResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'activity_type',
            'id' => $this->uuid,
            'attributes' => [
                'activity_category_id' => $this->activityCategory->uuid,
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
                'self' => route('api.v1.activityTypes.show', $this->uuid),
            ],
        ];
    }
}
