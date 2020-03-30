<?php

namespace App\Http\Resources\Activity;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Activity\ActivityTypeCategory as ActivityTypeCategoryResource;

/**
 * @extends JsonResource<\App\Models\Account\ActivityType>
 */
class ActivityType extends JsonResource
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
            'object' => 'activityType',
            'name' => $this->name,
            'location_type' => $this->location_type,
            'activity_type_category' => new ActivityTypeCategoryResource($this->category),
            'account' => [
                'id' => $this->account_id,
            ],
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
