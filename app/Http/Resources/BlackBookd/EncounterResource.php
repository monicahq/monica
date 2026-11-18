<?php

namespace App\Http\Resources\BlackBookd;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\BlackBookdEncounter
 */
class EncounterResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'vault_id' => $this->vault_id,
            'occurred_at' => DateHelper::getTimestamp($this->occurred_at),
            'location_label' => $this->location_label,
            'how_met_category' => $this->how_met_category,
            'how_met_app' => $this->how_met_app,
            'how_met_detail' => $this->how_met_detail,
            'protection_used' => $this->protection_used ?? [],
            'activity_tags' => $this->activity_tags ?? [],
            'substances' => $this->substances,
            'rating' => $this->rating,
            'private_notes' => $this->private_notes,
            'consent_notes' => $this->consent_notes,
            'contact_ids' => $this->whenLoaded('contacts', fn () => $this->contacts->pluck('id')),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}