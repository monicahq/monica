<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Entity */
class EntityResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'entity',
            'id' => $this->uuid,
            'attributes' => [
                'name' => $this->name,
                'entity_type_id' => $this->entityType->uuid,
                'initials' => $this->initials,
                'avatar_colour' => $this->avatar_colour,
                'avatar' => $this->avatarLocation(),
                'is_archived' => $this->is_archived,
                'structure_version_id' => $this->structure_version_id,
                'template_name' => $this->structureVersion->structure->owner?->name,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
        ];
    }

    /** @return array{id: string, url: string}|null */
    private function avatarLocation(): ?array
    {
        if ($this->resource->avatar === null) {
            return null;
        }

        return [
            'id' => $this->resource->avatar->uuid,
            'url' => route('files.show', ['vault' => $this->vault, 'file' => $this->resource->avatar]),
        ];
    }
}
