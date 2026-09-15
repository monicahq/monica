<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\StructureVersionPage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin StructureVersionPage */
class StructureVersionPageResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'structure_version_page',
            'id' => $this->uuid,
            'attributes' => [
                'key' => $this->key,
                'label' => $this->label,
                'position' => $this->position,
                'sections' => StructureVersionSectionResource::collection($this->assembly)->resolve(),
            ],
        ];
    }
}
