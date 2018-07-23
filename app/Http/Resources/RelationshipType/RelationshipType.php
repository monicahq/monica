<?php

namespace App\Http\Resources\RelationshipType;

use App\Helpers\DateHelper;
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
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
