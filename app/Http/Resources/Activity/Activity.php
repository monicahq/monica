<?php

namespace App\Http\Resources\Activity;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Activity\ActivityType as ActivityTypeResource;

class Activity extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'object' => 'activity',
            'summary' => $this->summary,
            'description' => $this->description,
            'date_it_happened' => DateHelper::getTimestamp($this->date_it_happened),
            'activity_type' => new ActivityTypeResource($this->type),
            'attendees' => [
                'total' => $this->contacts()->count(),
                'contacts' => $this->getContactsForAPI(),
            ],
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
