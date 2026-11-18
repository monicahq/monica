<?php

namespace App\Http\Resources\BlackBookd;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\BlackBookdDatingPipeline
 */
class DatingPipelineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'vault_id' => $this->vault_id,
            'contact_id' => $this->contact_id,
            'handle' => $this->handle,
            'app_source' => $this->app_source,
            'age' => $this->age,
            'metro_location' => $this->metro_location,
            'role' => $this->role,
            'looking_for' => $this->looking_for,
            'stage' => $this->stage,
            'last_message_from' => $this->last_message_from,
            'last_message_summary' => $this->last_message_summary,
            'last_message_at' => $this->last_message_at ? DateHelper::getTimestamp($this->last_message_at) : null,
            'what_he_said_matters' => $this->what_he_said_matters,
            'next_step' => $this->next_step,
            'red_flags_notes' => $this->red_flags_notes,
            'phone_numbers_exchanged' => $this->phone_numbers_exchanged,
            'boundary_held_do_not_reengage' => $this->boundary_held_do_not_reengage,
            'long_term_fit_score' => $this->long_term_fit_score,
            'long_term_fit_reason' => $this->long_term_fit_reason,
            'contact' => $this->whenLoaded('contact', fn () => [
                'id' => $this->contact->id,
                'name' => $this->contact->name,
            ]),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}