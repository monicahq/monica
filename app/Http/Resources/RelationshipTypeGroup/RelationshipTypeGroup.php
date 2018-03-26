<?php

namespace App\Http\Resources\RelationshipTypeGroup;

use Illuminate\Http\Resources\Json\Resource;

class RelationshipTypeGroup extends Resource
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
            'object' => 'relationshiptypegroup',
            'name' => $this->name,
            'delible' => (bool) $this->delible,
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => (is_null($this->created_at) ? null : $this->created_at->format(config('api.timestamp_format'))),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format(config('api.timestamp_format'))),
        ];
    }
}
