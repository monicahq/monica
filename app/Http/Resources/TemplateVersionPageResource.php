<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TemplateVersionPage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin TemplateVersionPage */
class TemplateVersionPageResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'template_version_page',
            'id' => $this->uuid,
            'attributes' => [
                'key' => $this->key,
                'label' => $this->label,
                'position' => $this->position,
                'sections' => TemplateVersionSectionResource::collection($this->assembly)->resolve(),
            ],
        ];
    }
}
