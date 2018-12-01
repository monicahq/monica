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



namespace App\Http\Location\Drivers;

use App\Helpers\RequestHelper;
use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;
use Illuminate\Support\Facades\Request;
use Stevebauman\Location\Drivers\Driver;

class CloudflareDriver extends Driver
{
    public function url()
    {
    }

    protected function hydrate(Position $position, Fluent $location)
    {
        $position->countryCode = $location->country_code;

        return $position;
    }

    protected function process($ip = null)
    {
        if (! is_null($ip)) {
            return $this->fallback->get($ip);
        }

        try {
            return $this->getCountry();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getCountry()
    {
        $country = Request::header('Cf-Ipcountry');

        if (! is_null($country)) {
            $response = ['country_code' => $country];

            return new Fluent($response);
        }

        return $this->fallback->get(RequestHelper::ip());
    }
}
