<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\EntityType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * A kind of object an account invented, as the API hands it out.
 *
 * The address is the uuid and never the primary key, which is what the model
 * says the uuid is there for. The browser reaches the same type by its slug,
 * so the slug is carried here too: it is what a caller builds a link into the
 * application from, and it is settled when the type is declared and never
 * rebuilt afterwards.
 *
 * Both names are given rather than one being derived from the other, because a
 * plural derived in English is wrong in most other languages.
 *
 * The account is deliberately absent, because it is always the caller's.
 *
 * @mixin EntityType
 */
class EntityTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
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
