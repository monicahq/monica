<?php

namespace App\Http\Resources\LifeEvent;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;

class LifeEventCategory extends Resource
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
            'object' => 'lifeeventcategory',
            'name' => $this->name,
            'core_monica_data' => (bool) $this->core_monica_data,
            'default_life_event_category_key' => $this->default_life_event_category_key,
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
