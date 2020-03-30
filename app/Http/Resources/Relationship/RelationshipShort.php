<?php

namespace App\Http\Resources\Relationship;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

/**
 * @extends JsonResource<\App\Models\Relationship\Relationship>
 */
class RelationshipShort extends JsonResource
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
