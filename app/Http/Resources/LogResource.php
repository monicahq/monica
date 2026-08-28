<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One thing that happened in an account, as the API hands it out.
 *
 * The entry carries both the raw name of the action, which is what a program
 * matches on, and the sentence a person reads, translated into the language on
 * the account. The address is here because it is what makes the journal worth
 * reading: the entry that came from somewhere its owner has never been is the
 * one to find. It is empty when the application acted on its own.
 *
 * What changed is deliberately not here. A field somebody marked sensitive
 * records that it changed and not what to, and the product specification has
 * not settled how those values may be kept, so the column stays out until it
 * does. Adding it later takes nothing away from anybody.
 *
 * @mixin Log
 */
class LogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
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
