<?php

namespace App\Http\Resources\Activity;

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
            'date_it_happened' => $this->date_it_happened->format(config('api.timestamp_format')),
            'activity_type' => new ActivityTypeResource($this->type),
            'attendees' => [
                'total' => $this->contacts()->count(),
                'contacts' => $this->getContactsForAPI(),
            ],
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => $this->created_at->format(config('api.timestamp_format')),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format(config('api.timestamp_format'))),
        ];
    }
}
