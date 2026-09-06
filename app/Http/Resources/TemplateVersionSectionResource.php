<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TemplateVersionSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin TemplateVersionSection */
class TemplateVersionSectionResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $sectionVersion = $this->sectionVersion;

        return [
            'type' => 'template_version_section',
            'id' => (string) $this->id,
            'attributes' => [
                'position' => $this->position,
                'title' => $this->title_override ?? $sectionVersion->section->name,
                'title_override' => $this->title_override,
                'section_id' => $sectionVersion->section->uuid,
                'section_name' => $sectionVersion->section->name,
                'section_version_number' => $sectionVersion->version_number,
                'section_version_status' => $sectionVersion->status->value,
                'is_collapsed_by_default' => $this->is_collapsed_by_default,
                'fields' => FieldResource::collection($sectionVersion->fields)->resolve(),
            ],
        ];
    }
}
