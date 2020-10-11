<?php

namespace App\Http\Resources\LifeEvent;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;
use App\Http\Resources\LifeEvent\LifeEventType as LifeEventTypeResource;

/**
 * @extends JsonResource<\App\Models\Contact\LifeEvent>
 */
class LifeEvent extends JsonResource
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
            'object' => 'lifeevent',
            'name' => $this->name,
            'note' => $this->note,
            'happened_at' => DateHelper::getTimestamp($this->happened_at),
            'life_event_type' => new LifeEventTypeResource($this->lifeEventType),
            'account' => [
                'id' => $this->account_id,
            ],
            'contact' => new ContactShortResource($this->contact),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
