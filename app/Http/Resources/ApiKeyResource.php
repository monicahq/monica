<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\ApiKeyScopeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;
use Override;

/** @mixin PersonalAccessToken */
class ApiKeyResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'api_key',
            'id' => (string) $this->id,
            'attributes' => [
                'name' => $this->name,
                'sensitive_fields' => in_array(ApiKeyScopeEnum::SensitiveFields->value, $this->abilities ?? [], true),
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
