<?php

namespace App\Http\Resources\Pet;

use Illuminate\Http\Resources\Json\Resource;

class PetCategory extends Resource
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
