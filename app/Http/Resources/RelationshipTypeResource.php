<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\RelationshipType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin RelationshipType */
class RelationshipTypeResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'relationship_type',
            'id' => $this->uuid,
            'attributes' => [
                'relationship_category_id' => $this->relationshipCategory->uuid,
                'name' => $this->name,
                'role_a' => $this->role_a,
                'role_b' => $this->role_b,
                'is_symmetric' => $this->is_symmetric,
                'system_behavior' => $this->system_behavior?->value,
                'position' => $this->position,
                'is_system' => $this->is_system,
                'is_active' => $this->is_active,
                'relationship_count' => $this->relationships_count ?? $this->relationshipCount(),
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.relationshipTypes.show', $this->uuid),
            ],
        ];
    }
}
