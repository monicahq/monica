<?php

namespace App\Http\Location\Drivers;

use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;
use Stevebauman\Location\Request;
use Stevebauman\Location\Drivers\Driver;

class CloudflareDriver extends Driver
{
    protected function process(Request $request): Fluent|false
    {
        try {
            $country = $request->getHeader('Cf-Ipcountry');

            if (! is_null($country)) {
                return new Fluent(['country_code' => $country]);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function hydrate(Position $position, Fluent $location): Position
    {
        $position->countryCode = $location->country_code;

        return $position;
    }
}
