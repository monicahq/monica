<?php

namespace App\Http\Resources\Reminder;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

/**
 * @extends JsonResource<\App\Models\Contact\ReminderOutbox>
 */
class ReminderOutbox extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'reminder_id' => $this->reminder_id,
            'object' => $this->nature,
            'planned_date' => $this->planned_date,
            'title' => $this->reminder->title,
            'description' => $this->reminder->description,
            'frequency_type' => $this->reminder->frequency_type,
            'frequency_number' => $this->reminder->frequency_number,
            'initial_date' => DateHelper::getTimestamp($this->reminder->initial_date),
            'delible' => (bool) $this->reminder->delible,
            'account' => [
                'id' => $this->account_id,
            ],
            'contact' => new ContactShortResource($this->reminder->contact),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
