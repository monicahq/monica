<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Contact
 */
class ContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'vault_id' => $this->vault_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'middle_name' => $this->middle_name,
            'nickname' => $this->nickname,
            'maiden_name' => $this->maiden_name,
            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
            'job_position' => $this->job_position,
            'is_favorite' => $this->is_favorite,
            'personal_note' => $this->personal_note,
            'listed' => $this->listed,
            'can_be_deleted' => $this->can_be_deleted,
            'last_updated_at' => DateHelper::getTimestamp($this->last_updated_at),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
            'links' => [
                'self' => route('api.contacts.show', ['vault' => $this->vault_id, 'contact' => $this->id]),
            ],
        ];
    }
}
