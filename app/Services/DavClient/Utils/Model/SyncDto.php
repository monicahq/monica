<?php

namespace App\Services\DavClient\Utils\Model;

use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Dav\DavClient;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;

class SyncDto
{
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
}
