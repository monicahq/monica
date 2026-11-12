<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\File;
use App\Models\PossessionValuation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin PossessionValuation */
class PossessionValuationResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['possession.vault', 'possession.valuations', 'documents']);

        return [
            'type' => 'possession_valuation',
            'id' => $this->uuid,
            'attributes' => [
                'type' => $this->type->value,
                'valued_on' => $this->valued_on->format('Y-m-d'),
                'confidence' => $this->confidence->value,
                'amount' => $this->amount,
                'currency' => $this->currency->value,
                'is_current' => $this->possession->currentValuation()?->is($this->resource) ?? false,
                'valuer' => $this->valuer,
                'method' => $this->method,
                'reference_number' => $this->reference_number,
                'source_url' => $this->source_url,
                'note' => $this->note,
                'documents' => $this->documents->map(fn (File $file): array => [
                    'id' => $file->uuid,
                    'name' => $file->original_name,
                    'mime_type' => $file->mime_type,
                    'size_in_bytes' => $file->size_in_bytes,
                ])->values()->all(),
                'created_at' => $this->created_at->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.possessions.valuations.show', [$this->possession->vault->slug, $this->possession->uuid, $this->uuid]),
                'web' => route('possessions.valuations.index', [$this->possession->vault, $this->possession]),
            ],
        ];
    }
}
