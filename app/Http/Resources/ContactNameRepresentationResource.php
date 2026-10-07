<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ContactNameRepresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin ContactNameRepresentation */
class ContactNameRepresentationResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'kind' => $this->kind->value,
            'language' => $this->language_tag,
            'script' => $this->script_code,
            'phonetic_system' => $this->phonetic_system,
            'formatted_name' => $this->formatted_name,
            'prefix' => $this->prefix,
            'given_name' => $this->given_name,
            'additional_name' => $this->additional_name,
            'family_name' => $this->family_name,
            'suffix' => $this->suffix,
            'name_order' => $this->name_order?->value,
            'position' => $this->position,
            'is_display' => $this->contact->display_name_representation_id === $this->id,
        ];
    }
}
