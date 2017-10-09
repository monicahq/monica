<?php

namespace App\Http\Resources\Activity;

use Illuminate\Http\Resources\Json\Resource;

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
            'type' => $this->key,
            'group' => $this->group->key,
            'location_type' => $this->location_type,
            'created_at' => $this->created_at->format(config('api.timestamp_format')),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format(config('api.timestamp_format'))),
        ];
    }
}
