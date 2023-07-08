<?php

namespace App\Domains\Contact\DavClient\Services\Utils\Traits;

use Illuminate\Support\Arr;

trait HasCapability
{
    /**
     * Check if the subscription has the give capability.
     */
    private function hasCapability(string $capability): bool
    {
        return Arr::get($this->subscription->capabilities, $capability, false);
    }
}
