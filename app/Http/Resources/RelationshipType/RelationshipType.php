<?php

namespace App\Http\Resources\RelationshipType;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @extends JsonResource<\App\Models\Relationship\RelationshipType>
 */
class RelationshipType extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
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
                'id' => $this->account_id,
            ],
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
