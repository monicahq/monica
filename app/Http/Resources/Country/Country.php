<?php

namespace App\Http\Resources\Country;

use App\Helpers\CountriesHelper;
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
            'id' => $this->resource,
            'object' => 'country',
            'name' => CountriesHelper::get($this->resource),
            'iso' => $this->resource,
        ];
    }
}
