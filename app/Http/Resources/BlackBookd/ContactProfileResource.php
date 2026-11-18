<?php

namespace App\Http\Resources\BlackBookd;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\BlackBookdContactProfile
 */
class ContactProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'contact_id' => $this->contact_id,
            'hiv_status' => $this->hiv_status,
            'on_prep' => $this->on_prep,
            'last_test_date' => $this->last_test_date?->format('Y-m-d'),
            'kinks' => $this->kinks,
            'boundaries' => $this->boundaries,
            'relationship_type' => $this->relationship_type,
            'notes' => $this->notes,
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}