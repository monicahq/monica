<?php

namespace App\Http\Resources\Address;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Country\Country as CountryResource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

/**
 * @extends JsonResource<\App\Models\Contact\Address>
 */
class Address extends JsonResource
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
            'object' => 'address',
            'name' => $this->name,
            'street' => $this->place->street,
            'city' => $this->place->city,
            'province' => $this->place->province,
            'postal_code' => $this->place->postal_code,
            'latitude' => $this->place->latitude,
            'longitude' => $this->place->longitude,
            'country' => new CountryResource($this->place->country),
            'url' => route('api.address', $this->id),
            'account' => [
                'id' => $this->account_id,
            ],
            'contact' => new ContactShortResource($this->contact),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
