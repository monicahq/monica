<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Log */
class LogResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'log',
            'id' => (string) $this->id,
            'attributes' => [
                'author' => $this->author,
                'action' => $this->action,
                'parameters' => $this->parameters,
                'description' => $this->description,
                'source' => $this->source->value,
                'ip_address' => $this->ip_address,
                'created_at' => $this->created_at->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.logs.show', $this->id),
            ],
        ];
    }
}
