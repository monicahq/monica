<?php

namespace App\Http\Resources\Address;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Country\Country as CountryResource;

class AddressShort extends Resource
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
            'object' => 'address',
            'name' => $this->name,
            'street' => $this->street,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'country' => new CountryResource($this->country),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
