<?php

namespace App\Domains\Contact\DavClient\Services\Utils\Traits;

use App\Domains\Contact\Dav\Web\Backend\CardDAV\CardDAVBackend;
use App\Models\AddressBookSubscription;

trait HasSubscription
{
    private AddressBookSubscription $subscription;

    public function withSubscription(AddressBookSubscription $subscription): self
    {
        $this->subscription = $subscription;

        return $this;
    }

    private ?CardDAVBackend $backend = null;

    /**
     * Get carddav backend.
     */
    protected function backend(): CardDAVBackend
    {
        return $backend ?? $backend = app(CardDAVBackend::class)->withUser($this->subscription->user);
    }
}
