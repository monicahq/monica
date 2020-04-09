<?php

namespace App\Http\Resources\Reminder;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

/**
 * @extends JsonResource<\App\Models\Contact\Reminder>
 */
class Reminder extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'object' => 'reminder',
            'title' => $this->title,
            'description' => $this->description,
            'frequency_type' => $this->frequency_type,
            'frequency_number' => $this->frequency_number,
            'initial_date' => DateHelper::getTimestamp($this->initial_date),
            'delible' => (bool) $this->delible,
            'account' => [
                'id' => $this->account_id,
            ],
            'contact' => new ContactShortResource($this->contact),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
