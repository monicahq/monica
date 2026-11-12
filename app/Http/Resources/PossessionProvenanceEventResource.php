<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PossessionProvenanceEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin PossessionProvenanceEvent */
class PossessionProvenanceEventResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['possession.vault', 'transaction']);

        return [
            'type' => 'possession_provenance_event',
            'id' => $this->uuid,
            'attributes' => [
                'type' => $this->type->value,
                'group' => $this->type->group(),
                'title' => $this->title,
                'date_precision' => $this->date_precision->value,
                'occurred_on' => $this->date_precision->format($this->occurred_on),
                'from' => $this->from,
                'to' => $this->to,
                'location' => $this->location,
                'description' => $this->description,
                'reference_number' => $this->reference_number,
                'source_url' => $this->source_url,
                'is_verified' => $this->is_verified,
                'transaction_id' => $this->transaction?->uuid,
                'created_at' => $this->created_at->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.possessions.provenance.show', [$this->possession->vault->slug, $this->possession->uuid, $this->uuid]),
                'web' => route('possessions.provenance.index', [$this->possession->vault, $this->possession]),
            ],
        ];
    }
}
