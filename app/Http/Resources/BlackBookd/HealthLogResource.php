<?php

namespace App\Http\Resources\BlackBookd;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\BlackBookdHealthLogEntry
 */
class HealthLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'vault_id' => $this->vault_id,
            'entry_type' => $this->entry_type,
            'occurred_on' => $this->occurred_on->format('Y-m-d'),
            'payload' => $this->payload,
            'notes' => $this->notes,
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}