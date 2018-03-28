<?php

namespace App\Http\Resources\RelationshipType;

use Illuminate\Http\Resources\Json\Resource;

class RelationshipType extends Resource
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
            'object' => 'relationshiptype',
            'name' => $this->name,
            'name_reverse_relationship' => $this->name_reverse_relationship,
            'relationship_type_group_id' => $this->relationship_type_group_id,
            'delible' => $this->delible,
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => $this->created_at->format(config('api.timestamp_format')),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format(config('api.timestamp_format'))),
        ];
    }
}
