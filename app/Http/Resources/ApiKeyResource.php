<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * A key somebody minted, as the API hands it out.
 *
 * What is missing from it is the point: the key itself never appears here, in
 * any form. Only a hash of it is written down, and even that is not something
 * the API says out loud. The one response that carries the readable value is
 * the one that created it, and it carries it beside this envelope rather than
 * inside it, so that no listing can ever be made to leak one.
 *
 * The model the key acts as is left out too. It is always the caller.
 *
 * @mixin PersonalAccessToken
 */
class ApiKeyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'api_key',
            'id' => (string) $this->id,
            'attributes' => [
                'name' => $this->name,
                'last_used_at' => $this->last_used_at?->timestamp,
                'expires_at' => $this->expires_at?->timestamp,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.apiKeys.show', $this->id),
            ],
        ];
    }
}
