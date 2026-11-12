<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PossessionCondition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin PossessionCondition */
class PossessionConditionResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'possession_condition',
            'id' => $this->uuid,
            'attributes' => [
                'name' => $this->name,
                'colour' => $this->colour,
                'icon' => $this->icon,
                'position' => $this->position,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.possessionConditions.show', $this->uuid),
                'web' => route('settings.possessionConditions.index'),
            ],
        ];
    }
}
