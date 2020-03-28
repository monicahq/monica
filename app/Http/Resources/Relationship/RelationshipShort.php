<?php

namespace App\Http\Resources\Relationship;

use App\Http\Resources\Contact\ContactShort as ContactShortResource;
use Illuminate\Http\Resources\Json\Resource;

class RelationshipShort extends Resource
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
            'relationship' => [
                'id' => $this->id,
                'name' => $this->relationshipType->name,
            ],
            'contact' => new ContactShortResource($this->ofContact),
        ];
    }
}
