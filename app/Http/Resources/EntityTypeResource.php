<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\EntityType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin EntityType */
class EntityTypeResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'entity_type',
            'id' => $this->uuid,
            'attributes' => [
                'slug' => $this->slug,
                'name_singular' => $this->name_singular,
                'name_plural' => $this->name_plural,
                'description' => $this->description,
                'icon' => $this->icon,
                'colour' => $this->colour,
                'position' => $this->position,
                'is_active' => $this->is_active,
                'entity_count' => $this->entity_count,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.entityTypes.show', $this->uuid),
            ],
        ];
    }
}
