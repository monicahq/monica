<?php

namespace App\Services\DavClient\Utils\Model;

use Illuminate\Support\Traits\Macroable;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Dav\DavClient;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;

class SyncDto
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * @var AddressBookSubscription
     */
    public $subscription;

    /**
     * @var DavClient
     */
    public $client;

    /**
     * @var CardDAVBackend
     */
    public $backend;

    /**
     * Sync the address book.
     */
    public function __construct(AddressBookSubscription $subscription, DavClient $client, CardDAVBackend $backend)
    {
        $this->subscription = $subscription;
        $this->client = $client;
        $this->backend = $backend;
    }

    /**
     * Get address book name.
     *
     * @return string
     */
    public function addressBookName(): string
    {
        return $this->subscription->addressbook->name;
    }

    /**
     * Execute a method against a new dav client instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->subscription->getClient()
            ->{$method}(...$parameters);
    }
}
