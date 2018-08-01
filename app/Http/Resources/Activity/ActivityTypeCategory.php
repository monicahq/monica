<?php

namespace App\Http\Resources\Activity;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;

class ActivityTypeCategory extends Resource
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
            'object' => 'activityTypeCategory',
            'name' => $this->name,
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
