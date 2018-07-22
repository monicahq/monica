<?php

namespace App\Http\Resources\Activity;

use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Activity\ActivityTypeCategory as ActivityTypeCategoryResource;

class ActivityType extends Resource
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
            'object' => 'activityType',
            'name' => $this->name,
            'location_type' => $this->location_type,
            'activity_type_category' => new ActivityTypeCategoryResource($this->category),
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => (is_null($this->created_at) ? null : $this->created_at->format(config('api.timestamp_format'))),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format(config('api.timestamp_format'))),
        ];
    }
}
