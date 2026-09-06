<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Template */
class TemplateResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'template',
            'id' => $this->uuid,
            'attributes' => [
                'slug' => $this->slug,
                'name' => $this->name,
                'description' => $this->description,
                'applies_to' => $this->applies_to->value,
                'entity_type_id' => $this->entityType?->uuid,
                'is_system' => $this->is_system,
                'is_active' => $this->is_active,
                'position' => $this->position,
                'published_version_number' => $this->publishedVersion?->version_number,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.templates.show', $this->uuid),
            ],
        ];
    }
}
