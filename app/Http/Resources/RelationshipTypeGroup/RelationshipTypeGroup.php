<?php

namespace App\Http\Resources\RelationshipTypeGroup;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;

class RelationshipTypeGroup extends Resource
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
            'object' => 'relationshiptypegroup',
            'name' => $this->name,
            'delible' => (bool) $this->delible,
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
