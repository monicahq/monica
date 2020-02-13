<?php

namespace App\Http\Resources\Group;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Contact\Contact as ContactResource;

class Group extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $contacts = $this->contacts;

        return [
            'id' => $this->id,
            'object' => 'group',
            'name' => $this->name,
            'contacts' => $contacts ? ContactResource::collection($contacts) : null,
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
