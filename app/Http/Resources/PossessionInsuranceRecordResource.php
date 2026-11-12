<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PossessionInsuranceRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin PossessionInsuranceRecord */
class PossessionInsuranceRecordResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['possession.vault', 'possession.insuranceRecords']);

        return [
            'type' => 'possession_insurance_record',
            'id' => $this->uuid,
            'attributes' => [
                'insurer' => $this->insurer,
                'status' => $this->status->value,
                'is_current' => $this->possession->currentInsuranceRecord()?->is($this->resource) ?? false,
                'policy_number' => $this->policy_number,
                'coverage' => $this->coverage,
                'insured_value' => $this->insured_value,
                'excess' => $this->excess,
                'currency' => $this->currency->value,
                'starts_on' => $this->starts_on?->format('Y-m-d'),
                'ends_on' => $this->ends_on?->format('Y-m-d'),
                'is_listed_individually' => $this->is_listed_individually,
                'contact_name' => $this->contact_name,
                'contact_email' => $this->contact_email,
                'contact_phone' => $this->contact_phone,
                'note' => $this->note,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.possessions.insuranceRecords.show', [$this->possession->vault->slug, $this->possession->uuid, $this->uuid]),
                'web' => route('possessions.insuranceRecords.index', [$this->possession->vault, $this->possession]),
            ],
        ];
    }
}
