<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PossessionTag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin PossessionTag */
class PossessionTagResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'possession_tag',
            'id' => $this->uuid,
            'attributes' => [
                'name' => $this->name,
                'colour' => $this->colour,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.possessionTags.show', $this->uuid),
                'web' => route('settings.possessionTags.index'),
            ],
        ];
    }
}
