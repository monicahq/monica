<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Relationship;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Relationship */
class RelationshipResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'relationship',
            'id' => $this->uuid,
            'attributes' => [
                'relationship_type' => [
                    'id' => $this->relationshipType->uuid,
                    'name' => $this->relationshipType->name,
                    'is_symmetric' => $this->relationshipType->is_symmetric,
                ],
                'contact_a' => [
                    'id' => $this->contactA->uuid,
                    'slug' => $this->contactA->slug,
                    'name' => $this->contactA->name,
                    'is_partial' => $this->contactA->is_partial,
                    'role' => $this->relationshipType->role_a,
                ],
                'contact_b' => [
                    'id' => $this->contactB->uuid,
                    'slug' => $this->contactB->slug,
                    'name' => $this->contactB->name,
                    'is_partial' => $this->contactB->is_partial,
                    'role' => $this->relationshipType->role_b,
                ],
                'status' => $this->status->value,
                'started_on' => $this->started_on?->format('Y-m-d'),
                'ended_on' => $this->ended_on?->format('Y-m-d'),
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.contacts.relationships.show', [$this->vault, $this->contactA, $this->resource]),
            ],
        ];
    }
}
