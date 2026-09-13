<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\RelationshipCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin RelationshipCategory */
class RelationshipCategoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'relationship_category',
            'id' => $this->uuid,
            'attributes' => [
                'name' => $this->name,
                'position' => $this->position,
                'is_system' => $this->is_system,
                'is_active' => $this->is_active,
                'type_count' => $this->types_count ?? $this->types()->count(),
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.relationshipCategories.show', $this->uuid),
            ],
        ];
    }
}
