<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MobileSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Passport\AccessToken;
use Override;

/** @mixin MobileSession */
class MobileSessionResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $token = $request->user()?->currentAccessToken();

        return [
            'type' => 'mobile_session',
            'id' => (string) $this->id,
            'attributes' => [
                'device_name' => $this->device_name,
                'is_current' => $token instanceof AccessToken && (string) $token->oauth_access_token_id === $this->access_token_id,
                'last_used_at' => $this->last_used_at->timestamp,
                'created_at' => $this->created_at?->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.mobileSessions.show', $this->id),
            ],
        ];
    }
}
