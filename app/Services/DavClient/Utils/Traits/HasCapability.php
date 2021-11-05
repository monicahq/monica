<?php

namespace App\Services\DavClient\Utils\Traits;

use Illuminate\Support\Arr;

trait HasCapability
{
    /**
     * Check if the subscription has the give capability.
     *
     * @param  string  $capability
     * @return bool
     */
    private function hasCapability(string $capability): bool
    {
        return Arr::get($this->sync->subscription->capabilities, $capability, false);
    }
}
