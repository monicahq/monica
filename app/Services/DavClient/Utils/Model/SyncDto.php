<?php

namespace App\Services\DavClient\Utils\Model;

use Illuminate\Support\Traits\Macroable;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Dav\DavClient;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;

/**
 * @method array propFind($properties, int $depth = 0, array $options = [], string $url = '')
 * @method array|string|null getProperty(string $property, string $url = '', array $options = [])
 * @method array syncCollection($properties, string $syncToken, array $options = [], string $url = '')
 * @method array addressbookQuery($properties, array $options = [], string $url = '')
 */
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
     * Sync the address book.
     */
    public function __construct(AddressBookSubscription $subscription, DavClient $client)
    {
        $this->subscription = $subscription;
        $this->client = $client;
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
     * Get carddav backend.
     *
     * @return CardDAVBackend
     */
    public function backend(): CardDAVBackend
    {
        return app(CardDAVBackend::class)->init($this->subscription->user);
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
