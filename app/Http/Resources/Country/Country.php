<?php

namespace App\Http\Resources\Country;

use App\Helpers\CountriesHelper;
use Illuminate\Http\Resources\Json\Resource;

class Country extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        if ($this->resource instanceof \PragmaRX\Countries\Package\Support\Collection) {
            $id = $this->resource->id;
            $name = $this->resource->country;
        } else {
            $id = $this->resource;
            $name = CountriesHelper::get($this->resource);
        }

        return [
            'id' => $id,
            'object' => 'country',
            'name' => $name,
            'iso' => $id,
        ];
    }
}
