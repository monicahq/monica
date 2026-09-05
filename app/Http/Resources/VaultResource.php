<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Vault */
class VaultResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'vault',
            'id' => $this->uuid,
            'attributes' => [
                'name' => $this->name,
                'description' => $this->description,
                'colour' => $this->colour,
                'position' => $this->position,
                'is_default' => $this->is_default,
                'contact_count' => $this->contact_count,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.vaults.show', $this->uuid),
            ],
        ];
    }
}
