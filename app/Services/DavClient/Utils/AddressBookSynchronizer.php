<?php

namespace App\Services\DavClient\Utils;

use Illuminate\Support\Str;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use App\Services\DavClient\Utils\Dav\DavClient;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Traits\HasCapability;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;

class AddressBookSynchronizer
{
    use HasCapability;

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

    public function __construct(AddressBookSubscription $subscription, DavClient $client, CardDAVBackend $backend)
    {
        $this->subscription = $subscription;
        $this->client = $client;
        $this->backend = $backend;
    }

    protected function subscription(): AddressBookSubscription
    {
        return $this->subscription;
    }

    /**
     * Sync the address book.
     */
    public function sync()
    {
        // Get changes to sync
        $localChanges = $this->backend->getChangesForAddressBook($this->subscription->addressbook->name, $this->subscription->localSyncToken, 1);

        // Get distant changes to sync
        $this->getDistantChanges()
            ->then(function ($changes) {
                // Get distant contacts
                (new AddressBookContactsUpdater($this))
                    ->updateContacts($changes);

                return $changes;
            })
            ->then(function ($changes) use ($localChanges) {
                if ($this->subscription->readonly) {
                    return;
                }

                return (new AddressBookContactsPusher($this))
                    ->pushContacts($changes, $localChanges);
            })
            ->wait();

        $token = $this->backend->getCurrentSyncToken($this->subscription->addressbook->name);

        $this->subscription->localSyncToken = $token->id;
        $this->subscription->save();
    }

    /**
     * Sync the address book.
     */
    public function forcesync()
    {
        // Get changes to sync
        $localChanges = $this->backend->getChangesForAddressBook($this->subscription->addressbook->name, $this->subscription->localSyncToken, 1);

        // Get actual list of contacts
        $localContacts = $this->backend->getObjects($this->subscription->addressbook->name);

        // Get distant changes to sync
        $this->getAllContactsEtag()
            ->then(function ($distContacts) use ($localContacts) {
                // Get missed contacts
                (new AddressBookContactsUpdater($this))
                    ->updateMissedContacts($localContacts, $distContacts);

                return $distContacts;
            })
            ->then(function ($distContacts) use ($localChanges, $localContacts) {
                if ($this->subscription->readonly) {
                    return;
                }

                return (new AddressBookContactsPusher($this))
                    ->pushContacts(collect(), $localChanges, $distContacts, $localContacts);
            })
            ->wait();
    }

    /**
     * Get distant changes to sync.
     *
     * @return PromiseInterface
     */
    private function getDistantChanges(): PromiseInterface
    {
        return $this->getDistantEtags()
          ->then(function ($collection) {
              return collect($collection)
                ->filter(function ($contact) {
                    // only return vcards
                    return isset($contact[200])
                        && Str::contains($contact[200]['{DAV:}getcontenttype'], 'text/vcard');
                })->filter(function ($contact, $href) {
                    // only new contact or contact with etag that match
                    $card = $this->backend->getCard($this->subscription->addressbook->name, $href);

                    return $card === false || $card['etag'] !== $contact[200]['{DAV:}getetag'];
                })->map(function ($contact, $href) {
                    return [
                        'href' => $href,
                        'etag' => $contact[200]['{DAV:}getetag'],
                    ];
                });
          });
    }

    /**
     * Get refreshed etags.
     *
     * @return PromiseInterface
     */
    private function getDistantEtags(): PromiseInterface
    {
        if ($this->hasCapability('syncCollection')) {
            // With sync-collection
            return $this->callSyncCollection();
        } else {
            // With PROPFIND
            return $this->client->propFindAsync('', [
                '{DAV:}getcontenttype',
                '{DAV:}getetag',
            ], 1);
        }
    }

    /**
     * Make sync-collection request.
     *
     * @return PromiseInterface
     */
    private function callSyncCollection(): PromiseInterface
    {
        // With sync-collection
        $syncToken = $this->subscription->syncToken ?? '';

        // get the current distant syncToken
        $distantSyncToken = $this->client->getProperty('{DAV:}sync-token');

        if ($syncToken == $distantSyncToken) {
            // no change at all
            return $this->emptyPromise();
        }

        // get sync
        return $this->client->syncCollectionAsync('', [
            '{DAV:}getcontenttype',
            '{DAV:}getetag',
        ], $syncToken)->then(function ($collection) {
            // save the new syncToken as current one
            if (array_key_exists('synctoken', $collection)) {
                $this->subscription->syncToken = $collection['synctoken'];
                $this->subscription->save();
            }

            return $collection;
        });
    }

    /**
     * Get all contacts etag.
     *
     * @return PromiseInterface
     */
    private function getAllContactsEtag(): PromiseInterface
    {
        if (! $this->hasCapability('addressbookQuery')) {
            return $this->emptyPromise();
        }

        return $this->client->addressbookQueryAsync('', [
            '{DAV:}getetag',
        ])->then(function ($datas) {
            return collect($datas)
                ->filter(function ($contact) {
                    return isset($contact[200]);
                })
                ->map(function ($contact, $href) {
                    return [
                        'href' => $href,
                        'etag' => $contact[200]['{DAV:}getetag'],
                    ];
                });
        });
    }

    /**
     * Get an empty Promise.
     *
     * @return PromiseInterface
     */
    private function emptyPromise(): PromiseInterface
    {
        $promise = new Promise(function () use (&$promise) {
            $promise->resolve([]);
        });

        return $promise;
    }
}
