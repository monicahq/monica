<?php

namespace App\Http\Resources\Country;

use Illuminate\Http\Resources\Json\Resource;

class Country extends Resource
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
            'object' => 'country',
            'name' => $this->country,
            'iso' => $this->iso,
        ];
    }
}
