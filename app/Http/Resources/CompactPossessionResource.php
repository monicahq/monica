<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Possession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Possession */
class CompactPossessionResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['vault', 'possessionType', 'location', 'valuations']);
        $currentValue = $this->currentValue();

        return [
            'type' => 'possession',
            'id' => $this->uuid,
            'attributes' => [
                'name' => $this->name,
                'status' => $this->status->value,
                'possession_type' => [
                    'id' => $this->possessionType->uuid,
                    'name' => $this->possessionType->name,
                ],
                'location' => $this->location === null ? null : [
                    'id' => $this->location->uuid,
                    'name' => $this->location->name,
                ],
                'current_value' => $currentValue === null ? null : [
                    'amount' => $currentValue['amount'],
                    'currency' => $currentValue['currency'],
                    'source' => $currentValue['isValuation'] ? 'valuation' : 'declared_value',
                ],
            ],
            'links' => [
                'self' => route('api.v1.possessions.show', [$this->vault, $this->resource]),
            ],
        ];
    }
}
