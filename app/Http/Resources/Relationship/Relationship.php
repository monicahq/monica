<?php

namespace App\Http\Resources\Relationship;

use App\Helpers\DateHelper;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;
use App\Http\Resources\RelationshipType\RelationshipType as RelationshipTypeResource;
use Illuminate\Http\Resources\Json\Resource;

class Relationship extends Resource
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
            'object' => 'relationship',
            'contact_is' => new ContactShortResource($this->contactIs),
            'relationship_type' => new RelationshipTypeResource($this->relationshipType),
            'of_contact' => new ContactShortResource($this->ofContact),
            'url' => route('api.relationship', $this->id),
            'account' => [
                'id' => $this->account_id,
            ],
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
