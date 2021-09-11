<?php

namespace App\Services\DavClient\Utils\Traits;

use Illuminate\Support\Arr;
use App\Models\Account\AddressBookSubscription;

trait HasCapability
{
    abstract protected function subscription(): AddressBookSubscription;

    /**
     * Check if the subscription has the give capability.
     *
     * @param  string  $capability
     * @return bool
     */
    private function hasCapability(string $capability): bool
    {
        return Arr::get($this->subscription()->capabilities, $capability, false);
    }
}
