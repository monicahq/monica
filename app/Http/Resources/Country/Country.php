<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



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
