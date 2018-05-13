<?php

namespace App\Http\Resources\Relationship;

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
            'created_at' => (is_null($this->created_at) ? null : $this->created_at->format(config('api.timestamp_format'))),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format(config('api.timestamp_format'))),
        ];
    }
}
