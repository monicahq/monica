<?php

namespace App\Http\Resources\Pet;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @extends JsonResource<\App\Models\Contact\PetCategory>
 */
class PetCategory extends JsonResource
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
            'object' => 'pet_category',
            'name' => $this->name,
            'is_common' => (bool) $this->is_common,
        ];
    }
}
