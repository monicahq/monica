<?php

namespace App\Http\Resources\Relationship;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;
use App\Http\Resources\RelationshipType\RelationshipType as RelationshipTypeResource;

class Relationship extends Resource
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
            'object' => 'relationship',
            'contact_is' => new ContactShortResource($this->contactIs),
            'relationship_type' => new RelationshipTypeResource($this->relationshipType),
            'of_contact' => new ContactShortResource($this->ofContact),
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
